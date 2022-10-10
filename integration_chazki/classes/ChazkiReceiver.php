<?php
/**
* 2007-2022 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if ($data = json_decode(file_get_contents('php://input'))) {
    require_once(dirname(__FILE__).'/ChazkiCollector.php');
    
    $updateResource = array(
        'orderStatus' => (int)$data->order_status,
        'orderID' => (string)$data->order_id
    );

    $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://"; 
    $url = str_replace(
        "modules/integration_chazki/classes/ChazkiReceiver.php",
        "",
        $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
    );

    $headers = apache_request_headers();
    $apiKey = $headers['x-api-key'];

    ChazkiCollector::updateOrderStatus(
        $updateResource,
        $url,
        $apiKey
    );
} else {
    $data = "no entro al if";
}
