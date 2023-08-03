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
class ChazkiOrders
{
    const CHAZKI_API_ORDERS = '/uploadClientOrders';

    public function __construct($module)
    {
        $this->module = $module;
        $this->chazki = new ChazkiApi($module);
    }

    public function validateOrder()
    {
        if (!ChazkiHelper::get(
            Tools::strtoupper(_DB_PREFIX_ . ChazkiInstallPanel::MODULE_API_KEY_NAME)
        )) {
            return false;
        }

        return true;
    }

    public function buildOrder($params)
    {
        $chazkiOrder = new stdClass();

        $chazkiOrder->enterpriseKey = ChazkiHelper::get(
            Tools::strtoupper(_DB_PREFIX_ . ChazkiInstallPanel::MODULE_API_KEY_NAME)
        );

        $serviceID = str_replace('_', ' ', Configuration::get(Tools::strtoupper(_DB_PREFIX_ . 'CHAZKI_SERVICE')));
        $branch = ChazkiHelper::get(Tools::strtoupper(_DB_PREFIX_ . ChazkiInstallPanel::MODULE_BRANCH_ID_NAME));
        $pickUpAddress = Configuration::get('PS_SHOP_ADDR1');
        $pickUpPostalCode = Configuration::get('PS_SHOP_CODE');
        $pickUpSecondaryReference = Configuration::get('PS_SHOP_CITY');
        $pickUpContactName = Configuration::get('PS_SHOP_NAME');
        $pickUpContactPhone = Configuration::get('PS_SHOP_PHONE');
        $pickUpContactEmail = Configuration::get('PS_SHOP_EMAIL');
        $dropPhone = $params['address']->phone_mobile ? $params['address']->phone_mobile : $params['address']->phone;
        $productPrice = $params['order']->total_products_wt ? $params['order']->total_products_wt : $params['order_details']->product_price;
        $packageQuantity = $params['order']->associations->order_rows ? count($params['order']->associations->order_rows) : $params['order_details']->product_quantity;

        $chazkiOrder->orders = [
            'trackCode' => $params['order']->reference,
            'paymentMethodID' => 'PAGADO',
            'paymentProofID' => 'BOLETA',
            'serviceID' => $serviceID ? $serviceID : 'SAME DAY',
            'packageEnvelope' => 'Caja',
            'packageWeight' => 0,
            'packageSizeID' => 'S',
            'packageQuantity' => (int) $packageQuantity,
            'productDescription' => $params['order_details']->product_name,
            'productPrice' => (float) $productPrice,
            'reverseLogistic' => 'NO',
            'crossdocking' => 'SI',
            'pickUpBranchID' => $branch ? $branch : '',
            'pickUpAddress' => $pickUpAddress ? $pickUpAddress : '',
            'pickUpPostalCode' => $pickUpPostalCode ? $pickUpPostalCode : '',
            'pickUpAddressReference' => '-',
            'pickUpPrimaryReference' => '-',
            'pickUpSecondaryReference' => $pickUpSecondaryReference ? $pickUpSecondaryReference : '-',
            'pickUpNotes' => '',
            'pickUpContactName' => $pickUpContactName ? $pickUpContactName : 'Not Name',
            'pickUpContactPhone' => $pickUpContactPhone ? $pickUpContactPhone : '000000000',
            'pickUpContactDocumentTypeID' => '-',
            'pickUpContactDocumentNumber' => '-',
            'pickUpContactEmail' => $pickUpContactEmail ? $pickUpContactEmail : 'a@a.com',
            'dropBranchID' => '',
            'dropAddress' => $params['address']->address1,
            'dropPostalCode' => $params['address']->postcode,
            'dropAddressReference' => '',
            'dropPrimaryReference' => '',
            'dropSecondaryReference' => $params['address']->city,
            'dropNotes' => '',
            'dropContactName' => $params['customer']->firstname . ' ' . $params['customer']->lastname,
            'dropContactPhone' => (int) $dropPhone,
            'dropContactDocumentTypeID' => '-',
            'dropContactDocumentNumber' => '-',
            'dropContactEmail' => $params['customer']->email ? $params['customer']->email : 'a@a.com',
            'providerID' => $params['order']->id,
            'providerName' => 'PRESTA',
            'shipmentPrice' => 0,
        ];

        return json_encode($chazkiOrder);
    }

    public function generateOrder($order)
    {
        $response = json_decode(
            $this->chazki->sendPost(
                self::CHAZKI_API_ORDERS,
                $order,
                []
            )
        );

        if (!$response) {
            return true;
        }
        $bodyJSON = json_decode($order);

        if ((int) $response->ordersWithoutErrors > 0) {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'orders` SET `shipping_number` = "' .
                $bodyJSON->orders->trackCode . '" WHERE `id_order` = ' . (int) $bodyJSON->orders->providerID;

            Db::getInstance()->Execute($sql);

            $sql = 'UPDATE `' . _DB_PREFIX_ . 'order_carrier` SET `tracking_number` = "' .
                $bodyJSON->orders->trackCode . '" WHERE `id_order` = ' . (int) $bodyJSON->orders->providerID;

            Db::getInstance()->Execute($sql);
        }
    }
}
