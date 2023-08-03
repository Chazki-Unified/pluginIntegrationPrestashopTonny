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
class ChazkiUninstall
{
    public function __construct($module)
    {
        $this->module = $module;
    }

    public function uninstall()
    {
        if (ChazkiHelper::get(
            Tools::strtoupper(_DB_PREFIX_ . 'CHAZKI_SERVICE_CARRIER_reference')
        )) {
            $carrier_obj = new Carrier(
                ChazkiHelper::get(
                    Tools::strtoupper(_DB_PREFIX_ . 'CHAZKI_SERVICE_CARRIER_reference')
                )
            );
            if ($carrier_obj) {
                $carrier_obj->delete();
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ .
                    'carrier` SET `deleted` = 1 WHERE `id_reference` = ' .
                    $carrier_obj->id_reference
                );
            }

            Configuration::deleteByName(
                Tools::strtoupper(
                    _DB_PREFIX_ . 'CHAZKI_SERVICE_CARRIER_reference'
                )
            );
            Configuration::deleteByName(
                Tools::strtoupper(
                    _DB_PREFIX_ . 'CHAZKI_SERVICE_CARRIER'
                )
            );
        }

        Configuration::deleteByName('INTEGATION_CHAZKI_LIVE_MODE');
        Configuration::deleteByName(
            Tools::strtoupper(
                _DB_PREFIX_ . ChazkiInstallPanel::MODULE_API_KEY_NAME
            )
        );
        Configuration::deleteByName(
            Tools::strtoupper(
                _DB_PREFIX_ . ChazkiInstallPanel::MODULE_BRANCH_ID_NAME
            )
        );
        Configuration::deleteByName(
            Tools::strtoupper(
                _DB_PREFIX_ . ChazkiInstallPanel::MODULE_SERVICE_NAME
            )
        );
        Configuration::deleteByName(
            Tools::strtoupper('CHAZKI_SHOP_URL')
        );
        Configuration::deleteByName(
            Tools::strtoupper(
                _DB_PREFIX_ . ChazkiInstallCarrier::CHAZKI_WEB_SERVICE_API_KEY
            )
        );
        Configuration::deleteByName(
            Tools::strtoupper(
                _DB_PREFIX_ . ChazkiInstallPanel::MODULE_STATUS_NAME
            )
        );
    }
}
