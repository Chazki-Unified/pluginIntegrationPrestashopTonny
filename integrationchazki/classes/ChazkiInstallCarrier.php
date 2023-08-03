<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
class ChazkiInstallCarrier
{
    public static $chazki_services = [
        'CHAZKI_SERVICE_CARRIER' => 'Chazki',
    ];

    const CHAZKI_TRACKING_URL_CARRIER = '/trackcodeTracking/@';
    const CARRIER_ID_SERVICE_CODE = 'CARRIER_ID_SERVICE_CODE';
    const CHAZKI_MODULE_KEY = 'CHAZKI_MODULE_KEY';
    const CHAZKI_WEB_SERVICE_API_KEY = 'CHAZKI_WEB_SERVICE_API_KEY';

    public function __construct($module)
    {
        $this->module = $module;
        $this->chazkiApi = new ChazkiApi($module);
    }

    public function generateKey()
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789';
        $key = '';

        for ($i = 0; $i < 32; $i = $i + 1) {
            $key = $key . $characters[rand(0, Tools::strlen($characters) - 1)];
        }

        ChazkiHelper::updateValue(
            Tools::strtoupper(_DB_PREFIX_ . self::CHAZKI_WEB_SERVICE_API_KEY),
            $key
        );

        return $key;
    }

    /**
     * creates a ChazkiAccess key
     *
     * @return void
     */
    public function chazkiAccess()
    {
        $chazkiAccess = new WebserviceKey();

        $chazkiAccess->key = $this->generateKey();
        $chazkiAccess->save();

        $permissions = [
            'customers' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
            'orders' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
            'addresses' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
            'order_details' => ['GET' => 1, 'POST' => 1, 'PUT' => 1, 'DELETE' => 1, 'HEAD' => 1],
        ];

        WebserviceKey::setPermissionForAccount($chazkiAccess->id, $permissions);
    }

    /**
     * enables webservice
     *
     * @return void
     * @throws PrestaShopDatabaseException
     */
    public function enableWebService()
    {
        if (ChazkiHelper::get('PS_WEBSERVICE') == 1) {
            $this->chazkiAccess();
        } else {
            ChazkiHelper::updateValue('PS_WEBSERVICE', 1);
            $this->chazkiAccess();
        }
    }

    /**
     * install carriers
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     */
    public function installCarriers()
    {
        $carrier_id_service_code = [];

        foreach (self::$chazki_services as $service_code => $name) {
            $added_carrier = $this->addCarrier($name, $service_code);

            if ($added_carrier) {
                $id_reference = $added_carrier->id_reference;

                if (!$id_reference) {
                    $id_reference = $added_carrier->id;
                }

                $carrier_id_service_code[$id_reference] = $service_code;
            }
        }

        ChazkiHelper::get(self::CARRIER_ID_SERVICE_CODE)
            ? ChazkiHelper::updateValue(self::CARRIER_ID_SERVICE_CODE, json_encode($carrier_id_service_code))
            : ChazkiHelper::set(self::CARRIER_ID_SERVICE_CODE, json_encode($carrier_id_service_code));
    }

    /**
     * Add a carrier
     *
     * @param string $name Carrier name
     * @param string $key Carrier ID
     *
     * @return bool|Carrier
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function addCarrier($name, $key)
    {
        $key = Tools::strtoupper($key);
        $id_reference = \Db::getInstance()->getValue(
            'SELECT value FROM ' . _DB_PREFIX_ .
            "configuration WHERE name LIKE '" . pSQL($key) .
            "' ORDER BY id_configuration DESC"
        );
        $carrier = Carrier::getCarrierByReference($id_reference);

        if (Validate::isLoadedObject($carrier)) {
            return $carrier; // Already added to DB
        }

        $carrier = new Carrier();
        $carrier->name = $name;
        $carrier->delay = [];
        $carrier->url = $this->chazkiApi->getUrlNintendo(self::CHAZKI_TRACKING_URL_CARRIER);
        $carrier->external_module_name = ChazkiHelper::NAMEL;
        $carrier->active = true;
        $carrier->shipping_external = true;
        $carrier->is_module = true;
        $carrier->need_range = true;

        foreach (Language::getLanguages() as $lang) {
            $id_lang = (int) $lang['id_lang'];
            $carrier->delay[$id_lang] = '-';
        }

        if ($carrier->add()) {
            @copy(
                dirname(__FILE__, 2) . '/views/img/logoChazki.jpg',
                _PS_SHIP_IMG_DIR_ . DIRECTORY_SEPARATOR . (int) $carrier->id . '.jpg'
            );

            $id_reference = (int) $carrier->id_reference ?: (int) $carrier->id;
            Configuration::updateValue(Tools::strtoupper(_DB_PREFIX_ . $key), $carrier->id);
            Configuration::updateValue(Tools::strtoupper(_DB_PREFIX_ . $key . '_reference'), $carrier->id);

            $this->addGroups($carrier);
            $this->addRanges($carrier);

            return $carrier;
        }

        return false;
    }

    /**
     * @param Carrier $carrier
     */
    protected function addGroups(Carrier $carrier)
    {
        $groups_ids = [];
        $groups = Group::getGroups(Context::getContext()->language->id);

        foreach ($groups as $group) {
            $groups_ids[] = $group['id_group'];
        }

        /* For v1.5.x.x where setGroups does not exists */
        if (method_exists($carrier, 'setGroups')) {
            $carrier->setGroups($groups_ids);
        } else {
            $this->setGroups($carrier, $groups_ids);
        }
    }

    /**
     * Set carrier-group relation (for PrestaShop v1.5.x.x)
     *
     * @param Carrier $carrier
     * @param $groups
     * @param bool $delete
     * @return bool
     */
    protected function setGroups(Carrier $carrier, $groups, $delete = true)
    {
        if ($delete) {
            Db::getInstance()
                ->execute('DELETE FROM ' . _DB_PREFIX_ . 'carrier_group WHERE id_carrier=' . (int) $carrier->id);
        }

        if (!is_array($groups) || !count($groups)) {
            return true;
        }

        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'carrier_group (id_carrier, id_group) VALUES ';

        foreach ($groups as $id_group) {
            $sql .= '(' . (int) $carrier->id . ', ' . (int) $id_group . '),';
        }

        return Db::getInstance()->execute(rtrim($sql, ','));
    }

    protected function addRanges($carrier)
    {
        $range_price = new RangePrice();
        $range_price->id_carrier = $carrier->id;
        $range_price->delimiter1 = '0';
        $range_price->delimiter2 = '100000';
        $range_price->add();

        $range_weight = new RangeWeight();
        $range_weight->id_carrier = $carrier->id;
        $range_weight->delimiter1 = '0';
        $range_weight->delimiter2 = '100000';
        $range_weight->add();

        $this->addZones($carrier, $range_price, $range_weight);
    }

    protected function addZones($carrier, $rangePrice, $rangeWeight)
    {
        $zones = Zone::getZones();

        foreach ($zones as $zone) {
            $carrier->addZone($zone['id_zone']);
            $carrier->addDeliveryPrice([[
                'id_carrier' => $carrier->id,
                'id_range_price' => (int) $rangePrice->id,
                'id_range_weight' => null,
                'id_zone' => (int) $zone['id_zone'],
                'price' => '25',
            ]]);
            $carrier->addDeliveryPrice([[
                'id_carrier' => $carrier->id,
                'id_range_price' => null,
                'id_range_weight' => (int) $rangeWeight->id,
                'id_zone' => (int) $zone['id_zone'],
                'price' => '25',
            ]]);
        }
    }
}
