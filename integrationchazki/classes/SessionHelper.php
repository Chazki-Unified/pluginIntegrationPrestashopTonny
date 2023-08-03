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
class SessionHelper
{
    public function __construct()
    {
    }

    public function __clone()
    {
    }

    public function __wakeup()
    {
    }

    private static $key_name = 'CHAZKI_HELPER';
    private static $data;
    private static $instance;

    private static function init()
    {
        if (self::$data !== null) {
            return;
        }
        self::$instance = new self();
        self::$key_name .= session_id();
        self::$data = json_decode(ChazkiHelper::get(self::$key_name), true);
        if (!is_array(self::$data)) {
            self::$data = [];
        }
    }

    public function __destruct()
    {
        ChazkiHelper::updateValue(self::$key_name, json_encode(self::$data));
    }

    public function get($name, $default = null)
    {
        self::init();
        $ret = self::$data[$name];
        unset(self::$data[$name]);
        return $ret ?: $default;
    }

    public function set($name, $value)
    {
        self::init();
        self::$data[$name] = $value;
    }
}
