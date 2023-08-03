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
class ChazkiWebhooks
{
    const CHAZKI_SAVE_CONFIG_WEBHOOK = '/saveConfigWebhook';
    const CHAZKI_UPDATE_BODY_WEBHOOK = '/updateBodyWebhook';

    public function __construct($module)
    {
        $this->module = $module;
        $this->chazki = new ChazkiApi($module);
    }

    public function saveConfig($api_key)
    {
        $json = json_decode(Tools::file_get_contents(dirname(dirname(__FILE__)) . '/templates/saveconfig.json'), true);
        $json['enterpriseKey'] = $api_key;
        $json['urlWebHook'] = Configuration::get('CHAZKI_SHOP_URL')
            . 'modules/integrationchazki/classes/ChazkiReceiver.php';
        $json['hookHeaders']['x-api-key'] = Configuration::get(
            Tools::strtoupper(_DB_PREFIX_ . 'CHAZKI_WEB_SERVICE_API_KEY')
        );
        $jsonConfig = json_encode($json);
        $this->chazki->sendPost(
            self::CHAZKI_SAVE_CONFIG_WEBHOOK,
            $jsonConfig,
            []
        );
    }

    public function updateBody($api_key)
    {
        $json = json_decode(Tools::file_get_contents(dirname(dirname(__FILE__)) . '/templates/bodywebhook.json'), true);
        $json['enterpriseKey'] = $api_key;

        $jsonConfig = json_encode($json);

        $this->chazki->sendPost(
            self::CHAZKI_UPDATE_BODY_WEBHOOK,
            $jsonConfig,
            []
        );
    }
}
