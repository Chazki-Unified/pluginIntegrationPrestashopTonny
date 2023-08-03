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
class ChazkiShippingCost
{
    const CHAZKI_API_SHIPPING = '/cfuntions-integration-prestashop/api/prestashop/quote';

    public function __construct($module)
    {
        $this->module = $module;
        $this->context = Context::getContext();
    }

    public function loadDropAddress($cart)
    {
        $this->address_obj = new Address($cart->id_address_delivery);
        $this->drop_address = ($this->address_obj->address1)
            ? $this->address_obj->address1
            : $this->address_obj->address2;

        if ($this->address_obj->city) {
            $this->drop_address = $this->drop_address . ', ' . $this->address_obj->city;
        }

        if ($this->address_obj->country) {
            $this->drop_address = $this->drop_address . ', ' . $this->address_obj->country;
        }
    }

    public function loadPickupAddress()
    {
        $this->service_name = str_replace('_', ' ', ChazkiHelper::get(
            Tools::strtoupper(
                _DB_PREFIX_ . ChazkiInstallPanel::MODULE_SERVICE_NAME
            )
        ));
        $this->enterprise_key = ChazkiHelper::get(
            Tools::strtoupper(
                _DB_PREFIX_ . ChazkiInstallPanel::MODULE_API_KEY_NAME
            )
        );

        if (ChazkiHelper::get(Tools::strtoupper(_DB_PREFIX_ . ChazkiInstallPanel::MODULE_BRANCH_ID_NAME))) {
            $this->pickup_address = ChazkiHelper::get(
                Tools::strtoupper(_DB_PREFIX_ . ChazkiInstallPanel::MODULE_BRANCH_ID_NAME)
            );
            $this->is_branch = true;
        } else {
            $this->pickup_address = ChazkiHelper::get(Tools::strtoupper('PS_SHOP_ADDR1'));

            if (ChazkiHelper::get(Tools::strtoupper('PS_SHOP_CITY'))) {
                $this->pickup_address = $this->pickup_address . ', ' .
                    ChazkiHelper::get(Tools::strtoupper('PS_SHOP_CITY'));
            }

            if (ChazkiHelper::get(Tools::strtoupper('PS_SHOP_COUNTRY'))) {
                $this->pickup_address = $this->pickup_address . ', ' .
                    ChazkiHelper::get(Tools::strtoupper('PS_SHOP_COUNTRY'));
            }
        }
    }

    protected function getShippingCost()
    {
        $bodyObj = [
            'pickupAddress' => $this->pickup_address,
            'serviceName' => $this->service_name,
            'dropAddress' => [$this->drop_address],
        ];

        if ($this->is_branch) {
            $bodyObj['isBranch'] = true;
        }

        $bodyJSON = new stdClass();
        $bodyJSON = json_encode($bodyObj);

        $api_chazki = new ChazkiApi($this->module);

        $responseJson = $api_chazki->sendPost(
            self::CHAZKI_API_SHIPPING,
            $bodyJSON,
            ['enterprise-key:' . $this->enterprise_key]
        );

        if (!$responseJson) {
            return false;
        }

        $response = json_decode($responseJson);

        if (!$response->success) {
            return false;
        }

        return (!$response) ? false : $response->quote;
    }

    public function run($cart, $shipping_fees)
    {
        $this->loadDropAddress($cart);
        if (!$this->drop_address) {
            return false;
        }
        $this->loadPickupAddress();
        $quote = $this->getShippingCost();

        if (!$quote) {
            return false;
        }

        return ($quote) ? $quote : $shipping_fees;
    }
}
