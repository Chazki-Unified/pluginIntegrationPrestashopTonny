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
    exit;
}

require_once dirname(__FILE__) . '/classes/ChazkiInstallCarrier.php';
require_once dirname(__FILE__) . '/classes/ChazkiInstallPanel.php';
require_once dirname(__FILE__) . '/classes/ChazkiUninstall.php';
require_once dirname(__FILE__) . '/classes/ChazkiCollector.php';
require_once dirname(__FILE__) . '/classes/ChazkiHelper.php';
require_once dirname(__FILE__) . '/classes/ChazkiApi.php';
require_once dirname(__FILE__) . '/classes/ChazkiShippingCost.php';
require_once dirname(__FILE__) . '/classes/ChazkiOrders.php';
require_once dirname(__FILE__) . '/classes/ChazkiWebhooks.php';

class IntegrationChazki extends CarrierModule
{
    protected $config_form = false;
    protected $carrier_id_service_code;
    const HOOKS = [
        'header',
        'backOfficeHeader',
        'updateCarrier',
        'actionCarrierUpdate',
        'displayAdminOrder',
        'actionValidateOrder',
        'actionOrderGridDefinitionModifier',
        'actionPaymentConfirmation',
        'actionOrderGridQueryBuilderModifier',
    ];

    public function __construct()
    {
        $this->name = 'integrationchazki';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Chazki';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Integration Chazki');
        $this->description = $this->l('Integration of chazki for acount of service');

        $this->confirmUninstall = $this->l('¿Esta seguro que desea desinstalar?');

        $this->chazki_carrier = new ChazkiInstallCarrier($this);
        $this->chazki_panel = new ChazkiInstallPanel($this);
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => _PS_VERSION_,
        ];
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        $this->chazki_carrier->installCarriers();
        $this->chazki_carrier->enableWebService();
        $baseUrl = $this->getUrl();
        Configuration::updateValue('CHAZKI_SHOP_URL', $baseUrl);
        Configuration::updateValue('INTEGATION_CHAZKI_LIVE_MODE', false);

        return parent::install() && $this->registerHook(self::HOOKS);
    }

    public function uninstall()
    {
        $chazki_uninstall = new ChazkiUninstall($this);
        $chazki_uninstall->uninstall();

        return parent::uninstall();
    }

    public function getUrl()
    {
        if (Configuration::get(Tools::strtoupper(_DB_PREFIX_ . 'SSL_ENABLED'))) {
            $domain = \Db::getInstance()->getValue(
                'SELECT `domain_ssl` FROM `' . _DB_PREFIX_ . 'shop_url` WHERE main = 1'
            );
            $uri = \Db::getInstance()->getValue(
                'SELECT `physical_uri` FROM `' . _DB_PREFIX_ . 'shop_url` WHERE main = 1'
            );
            $url = 'https://' . $domain . '' . $uri;
        } else {
            $domain = \Db::getInstance()->getValue(
                'SELECT `domain` FROM `' . _DB_PREFIX_ . 'shop_url` WHERE main = 1'
            );
            $uri = \Db::getInstance()->getValue(
                'SELECT `physical_uri` FROM `' . _DB_PREFIX_ . 'shop_url` WHERE main = 1'
            );
            $url = 'http://' . $domain . '' . $uri;
        }

        return $url;
    }
    /**
     * Load the configuration form
     */
    public function getContent()
    {
        return $this->chazki_panel->getContent();
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        // if (Context::getContext()->customer->logged == true)
        // {
        $chazki_ship = new ChazkiShippingCost($this);
        $shipping_cost = $chazki_ship->run($params, $shipping_cost);
        // }

        return $shipping_cost;
    }

    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, 0);
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function hookUpdateCarrier($params)
    {
        return $params;
    }

    public function hookActionPaymentConfirmation($params)
    {
        if (Configuration::get(Tools::strtoupper(_DB_PREFIX_ . 'CHAZKI_STATUS')) == 'PAYMENT') {
            $order_id = $params['id_order'];
            $chazkiCollector = new ChazkiCollector($this);

            $chazkiOrder = $chazkiCollector->getData(
                $order_id
            );

            if (!$chazkiOrder) {
                return false;
            }

            $new_order = new ChazkiOrders($this);

            if ($new_order->validateOrder()) {
                $chazkiOrderReturn = $new_order->buildOrder($chazkiOrder);
                $new_order->generateOrder($chazkiOrderReturn);
            }
        }
    }

    public function hookActionValidateOrder($params)
    {
        if (Configuration::get(Tools::strtoupper(_DB_PREFIX_ . 'CHAZKI_STATUS')) == 'NEW') {
            $orderObj = $params['order'];
            $chazkiCollector = new ChazkiCollector($this);

            $chazkiOrder = $chazkiCollector->getData(
                $orderObj->id
            );

            if (!$chazkiOrder) {
                return false;
            }

            $new_order = new ChazkiOrders($this);

            if ($new_order->validateOrder()) {
                $chazkiOrderReturn = $new_order->buildOrder($chazkiOrder);
                $new_order->generateOrder($chazkiOrderReturn);
            }
        }
    }

    public function hookActionCarrierUpdate($params)
    {
        if ($params['carrier']->id_reference == Configuration::get(Tools::strtoupper(_DB_PREFIX_ . 'CHAZKI_SERVICE_CARRIER_reference'))) {
            Configuration::updateValue(
                Tools::strtoupper(_DB_PREFIX_ . 'CHAZKI_SERVICE_CARRIER'),
                $params['carrier']->id
            );
        }
    }

    /**
     * Hook allows to modify Order grid definition since 1.7.7.0
     *
     * @param array $params
     */
    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        if (empty($params['definition'])) {
            return;
        }

        /** @var PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface $definition */
        $definition = $params['definition'];

        $column = new PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\LinkColumn('link_label');
        $column->setName($this->l('Etiqueta Chazki'));
        $column->setOptions([
            'field' => 'chazki_label',
            'route' => 'ps_integrationchazki_label_orders',
            'route_param_name' => 'reference',
            'route_param_field' => 'chazki_label',
            'target' => '_blank',
        ]);

        $definition
            ->getColumns()
            ->addAfter(
                'payment',
                $column
            )
        ;
    }

    /**
     * Hook allows to modify Order query builder and add custom sql statements since 1.7.7.0
     *
     * @param array $params
     */
    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        if (empty($params['search_query_builder']) || empty($params['search_criteria'])) {
            return;
        }

        /** @var Doctrine\DBAL\Query\QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        $searchQueryBuilder->addSelect(
            'o.`id_carrier`, car.`id_reference` AS `carrier_reference`, CASE WHEN LOCATE( "CHAZKI", UPPER(car.`name`)) > 0 THEN o.`reference` ELSE "" END AS `chazki_label`'
        );

        $searchQueryBuilder->leftJoin(
            'o',
            '`' . _DB_PREFIX_ . 'carrier`',
            'car',
            'car.`id_carrier` = o.`id_carrier`'
        );
    }
}
