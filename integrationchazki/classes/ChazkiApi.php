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
class ChazkiApi
{
    const BETA_ENV = 'https://us-central1-chazki-link.cloudfunctions.net';
    const PROD_ENV = 'https://us-central1-chazki-link.cloudfunctions.net';
    const CHAZKI_BETA = 'https://nintendo.chazki.com';

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function sendPost($postUrl, $params, $headers)
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->module->l('You have to enable the cURL extension '
                . 'on your server to install this module');
            return false;
        }

        if ($headers && count($headers) > 0) {
            array_push($headers, 'Content-Type:application/json');
        } else {
            $headers = ['Content-Type:application/json'];
        }

        $url = self::BETA_ENV . $postUrl;
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $params,
            ]
        );

        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_status > 499) {
            return false;
        }

        return $response;
    }

    public function getUrlNintendo($url)
    {
        return self::CHAZKI_BETA . $url;
    }
}
