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
class ChazkiCollector
{
    public function __construct($module)
    {
        $this->module = $module;
        $this->url = Configuration::get('CHAZKI_SHOP_URL');
        $this->shopKey = ChazkiHelper::get(
            Tools::strtoupper(
                _DB_PREFIX_ . ChazkiInstallCarrier::CHAZKI_WEB_SERVICE_API_KEY
            )
        );
    }

    protected function sendApiShop($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => ['Content-Type:application/json'],
            CURLOPT_USERPWD => $this->shopKey . ":''",
        ]);

        return curl_exec($curl);
    }

    public function getAddress($resource_id)
    {
        $url = $this->url . 'api/addresses/' . $resource_id . '?output_format=JSON';
        $response = $this->sendApiShop($url);

        return $response;
    }

    public function getCustomers($resource_id)
    {
        $url = $this->url . 'api/customers/' . $resource_id . '?output_format=JSON';
        $response = $this->sendApiShop($url);

        return $response;
    }

    public function getOrder($resource_id)
    {
        $url = $this->url . 'api/orders/' . $resource_id . '?output_format=JSON';
        $response = $this->sendApiShop($url);

        return $response;
    }

    public function getOrderXML($resource_id)
    {
        $url = $this->url . 'api/orders/' . $resource_id;
        $response = $this->sendApiShop($url);

        return $response;
    }

    public function getOrderDet($resource_id)
    {
        $url = $this->url . 'api/order_details/' . $resource_id . '?output_format=JSON';
        $response = $this->sendApiShop($url);

        return $response;
    }

    public static function updateOrderStatus($resource, $urlApi, $keyApi)
    {
        $curl = curl_init();
        $resource_id = $resource['orderID'];
        $url = $urlApi . 'api/orders/' . $resource_id;
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_USERPWD => $keyApi . ":''",
        ]);

        $orderData = simplexml_load_string(curl_exec($curl), 'SimpleXMLElement', LIBXML_NOCDATA);

        $updatedfields = $orderData->order->children();
        $updatedfields->current_state = $resource['orderStatus'];

        $curl = curl_init();
        $url = $urlApi . 'api/orders/' . $resource_id;
        curl_setopt_array(
            $curl,
            [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url,
                CURLOPT_USERPWD => $keyApi . ":''",
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => $orderData->asXML(),
            ]
        );

        curl_exec($curl);
    }

    public function getData($order_id)
    {
        $order_decoded = json_decode(
            $this->getOrder('' . $order_id)
        );

        $id_carrier_bd = Configuration::get(Tools::strtoupper(_DB_PREFIX_ . 'CHAZKI_SERVICE_CARRIER'));
        if ('' . $order_decoded->order->id_carrier != '' . $id_carrier_bd) {
            return false;
        }

        $address_decoded = json_decode(
            $this->getAddress('' . $order_decoded->order->id_address_delivery)
        );

        $customer_decoded = json_decode(
            $this->getCustomers('' . $order_decoded->order->id_customer)
        );

        $order_details_decoded = json_decode(
            $this->getOrderDet($order_decoded->order->associations->order_rows[0]->id)
        );

        return [
            'customer' => $customer_decoded->customer,
            'address' => $address_decoded->address,
            'order' => $order_decoded->order,
            'order_details' => $order_details_decoded->order_detail,
        ];
    }
}
