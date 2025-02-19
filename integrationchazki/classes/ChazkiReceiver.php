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
require_once dirname(__FILE__) . '/../../../config/config.inc.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $headers = apache_request_headers();
    $apiKey = $headers['x-api-key'];

    if ($apiKey) {
        if ($data = json_decode(Tools::file_get_contents('php://input'))) {
            require_once dirname(__FILE__) . '/ChazkiCollector.php';

            $updateResource = [
                'orderStatus' => (int) $data->order_status,
                'orderID' => (string) $data->order_id,
            ];

            $protocol = (
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443
            ) ? 'https://'
            : 'http://';

            $url = str_replace(
                'modules/integrationchazki/classes/ChazkiReceiver.php',
                '',
                $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
            );

            ChazkiCollector::updateOrderStatus(
                $updateResource,
                $url,
                $apiKey
            );

            $bodyRes = [
                'success' => true,
                'message' => 'successful update',
            ];

            echo json_encode($bodyRes);
        } else {
            http_response_code(401);
            $bodyRes = [
                'success' => false,
                'message' => 'data not found, please check body.',
            ];

            echo json_encode($bodyRes);
        }
    } else {
        http_response_code(403);
        $bodyRes = [
            'success' => false,
            'message' => 'not authorization',
        ];

        echo json_encode($bodyRes);
    }
} else {
    http_response_code(405);
}
