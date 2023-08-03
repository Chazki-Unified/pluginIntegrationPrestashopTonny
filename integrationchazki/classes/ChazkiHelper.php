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
if (!defined('_PS_VERSION_')) {
    return;
}

class ChazkiHelper
{
    const NAMEL = 'integrationchazki';
    const MSG_TYPE_ERROR = 'error';
    const MSG_TYPE_OK = 'ok';

    protected static $session;

    /**
     * @param $key
     * @param null $id_lang
     * @param null $id_shop_group
     * @param null $id_shop
     * @return string
     */
    public static function get($key, $id_lang = null, $id_shop_group = null, $id_shop = null)
    {
        return Configuration::get($key, $id_lang, $id_shop_group, $id_shop);
    }

    /**
     * @param $key
     * @param $values
     * @param bool $html
     * @param null $id_shop_group
     * @param null $id_shop
     * @return bool
     */
    public static function updateValue($key, $values, $html = false, $id_shop_group = null, $id_shop = null)
    {
        return Configuration::updateValue($key, $values, $html, $id_shop_group, $id_shop);
    }

    /**
     * @param $key
     * @param $values
     * @param null $id_shop_group
     * @param null $id_shop
     */
    public static function set($key, $values, $id_shop_group = null, $id_shop = null)
    {
        return Configuration::set($key, $values, $id_shop_group, $id_shop);
    }

    /**
     * @param $key
     * @param $values
     * @param bool $html
     * @return bool
     */
    public static function updateGlobalValue($key, $values, $html = false)
    {
        return Configuration::updateGlobalValue($key, $values, $html);
    }

    /**
     * @param $key
     * @return bool
     */
    public static function deleteByName($key)
    {
        return Configuration::deleteByName($key);
    }

    /**
     * Add to the storage of deferred messages
     *
     * @param $msg
     * @param $type
     */
    public static function setNotification($msg, $type = self::MSG_TYPE_OK)
    {
        $session = self::getSession();

        $data = $session->get(self::NAMEL, []);
        $data['notifications'][$type][] = self::l($msg);
        $session->set(self::NAMEL, $data);
    }

    /**
     * get session storage
     *
     * @return SessionHelper|\Symfony\Component\HttpFoundation\Session\Session|null
     */
    public static function getSession()
    {
        if (!self::$session) {
            self::$session = class_exists('\Symfony\Component\HttpFoundation\Session\Session')
                ? new \Symfony\Component\HttpFoundation\Session\Session()
                : new SessionHelper();
        }
        return self::$session;
    }

    /**
     * Translator alias
     *
     * @param $string string to translate
     * @return mixed|string
     */
    public static function l($string)
    {
        $module = Module::getInstanceByName(self::NAMEL);

        return $module->l($string);
    }

    /**
     * Console log
     * @param $msg
     * @param $with_script_tags
     */
    public static function consoleLog($msg, $with_script_tags = true)
    {
        $js_code = 'console.log(' . json_encode($msg, JSON_HEX_TAG) . ');';

        if ($with_script_tags) {
            $js_code = '<script>' . $js_code . '</script>';
        }

        echo $js_code;
    }
}
