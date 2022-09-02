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

if (!defined('_PS_VERSION_')) {
    exit;
}

class IntegracionChazki extends CarrierModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'IntegracionChazki';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'chazki';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Integracion Chazki');
        $this->description = $this->l('Este modulo ayuda a la comunicacion entre Chazki y PrestaShop ');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {

        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $orderObj = new stdClass();
        $orderJSON = new stdClass();

        $orderObj->enterpriseKey = 'b128f53b-a6c7-4831-83dc-817fdf96dc08';
        $orderObj->orders = array(
            'trackCode' => 'pruebaPS0004',
            'paymentMethodID' => 'PAGADO',
            'paymentProofID' => 'BOLETA',
            'serviceID' => 'EXPRESS',
            'packageEnvelope' => 'Caja',
            'packageWeight' => 1,
            'packageSizeID' => 'XL',
            'packageQuantity' => 1,
            'productDescription' => 'PRODUCTOS DEFAULT DE PRUEBA',
            'productPrice' => 10,
            'reverseLogistic' => 'NO',
            'crossdocking' => 'NO',
            'pickUpBranchID' => '',
            'pickUpAddress' => 'Juan Bielovucich Cavalier 1377',
            'pickUpPostalCode' => '',
            'pickUpAddressReference' => '-',
            'pickUpPrimaryReference' => 'Lince',
            'pickUpSecondaryReference' => 'LIMA',
            'pickUpNotes' => '',
            'pickUpContactName' => 'contactName',
            'pickUpContactPhone' => '12345678',
            'pickUpContactDocumentTypeID' => 'RUC',
            'pickUpContactDocumentNumber' => '12345678',
            'pickUpContactEmail' => 'xxxxxx@yyyyy.zzz',
            'dropBranchID' => '',
            'dropAddress' => 'Alberto Alexander 2321',
            'dropPostalCode' => '',
            'dropAddressReference' => '',
            'dropPrimaryReference' => 'Lince',
            'dropSecondaryReference' => 'LIMA',
            'dropNotes' => 'Notas',
            'dropContactName' => 'CONTACTO',
            'dropContactPhone' => '12345678 ',
            'dropContactDocumentTypeID' => 'DNI',
            'dropContactDocumentNumber' => '12345678',
            'dropContactEmail' => 'xxxxxx@yyyyy.zzz',
            'shipmentPrice' => 0,
            'providerName' => 'SHOPIFY',
            'providerID' => 'MIA00000050CL1'
        );

        $orderJSON = json_encode($orderObj);

        $this->sendOrderChazki('https://us-central1-chazki-link-beta.cloudfunctions.net/uploadClientOrders', $orderJSON);

        $carrier = $this->addCarrier();
        $this->addZones($carrier);
        $this->addGroups($carrier);
        $this->addRanges($carrier);
        Configuration::updateValue('INTEGRACIONCHAZKI_LIVE_MODE', false);


        /* 
            actionValidateOrder
            actionOrderStatusUpdate
        */
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('updateCarrier') &&
            // $this->registerHook('actionValidateOrder') &&
            // $this->registerHook('actionOrderStatusUpdate') &&
            $this->registerHook('displayOrderConfirmation');
    }

    public function uninstall()
    {
        Configuration::deleteByName('INTEGRACIONCHAZKI_LIVE_MODE');

        return parent::uninstall();
    }

    /** 
     * Send an order 
     */

    private static function sendOrderChazki($postUrl, $params)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $postUrl,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => array('Content-Type:application/json'),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $params,
        ));

        $response = curl_exec($curl);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        echo $http_status;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitIntegracionChazkiModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * actionValiteOrder function
     */
    // protected function hookActionValitadeOrder($params)
    // {
    //     /** 
    //      * code here
    //     */
    // }

    // /**
    //  * actionOrderStatusUpdate function
    //  */
    // protected function hookActionOrderStatusUpdate($params)
    // {
    //     /** 
    //      * code here
    //     */
    // }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitIntegracionChazkiModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'INTEGRACIONCHAZKI_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'INTEGRACIONCHAZKI_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'INTEGRACIONCHAZKI_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'INTEGRACIONCHAZKI_LIVE_MODE' => Configuration::get('INTEGRACIONCHAZKI_LIVE_MODE', true),
            'INTEGRACIONCHAZKI_ACCOUNT_EMAIL' => Configuration::get('INTEGRACIONCHAZKI_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'INTEGRACIONCHAZKI_ACCOUNT_PASSWORD' => Configuration::get('INTEGRACIONCHAZKI_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        if (Context::getContext()->customer->logged == true)
        {
            $id_address_delivery = Context::getContext()->cart->id_address_delivery;
            $address = new Address($id_address_delivery);

            /**
             * Send the details through the API
             * Return the price sent by the API
             */
            return 10;
        }

        return $shipping_cost;
    }

    public function getOrderShippingCostExternal($params)
    {
        return true;
    }

    protected function addCarrier()
    {
        $carrier = new Carrier();

        $carrier->name = $this->l('My super carrier');
        $carrier->is_module = true;
        $carrier->active = 1;
        $carrier->range_behavior = 1;
        $carrier->need_range = 1;
        $carrier->shipping_external = true;
        $carrier->range_behavior = 0;
        $carrier->external_module_name = $this->name;
        $carrier->shipping_method = 2;

        foreach (Language::getLanguages() as $lang)
            $carrier->delay[$lang['id_lang']] = $this->l('Super fast delivery');

        if ($carrier->add() == true)
        {
            @copy(dirname(__FILE__).'/views/img/carrier_image.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg');
            Configuration::updateValue('MYSHIPPINGMODULE_CARRIER_ID', (int)$carrier->id);
            return $carrier;
        }

        return false;
    }

    protected function addGroups($carrier)
    {
        $groups_ids = array();
        $groups = Group::getGroups(Context::getContext()->language->id);
        foreach ($groups as $group)
            $groups_ids[] = $group['id_group'];

        $carrier->setGroups($groups_ids);
    }

    protected function addRanges($carrier)
    {
        $range_price = new RangePrice();
        $range_price->id_carrier = $carrier->id;
        $range_price->delimiter1 = '0';
        $range_price->delimiter2 = '10000';
        $range_price->add();

        $range_weight = new RangeWeight();
        $range_weight->id_carrier = $carrier->id;
        $range_weight->delimiter1 = '0';
        $range_weight->delimiter2 = '10000';
        $range_weight->add();
    }

    protected function addZones($carrier)
    {
        $zones = Zone::getZones();

        foreach ($zones as $zone)
            $carrier->addZone($zone['id_zone']);
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookUpdateCarrier($params)
    {
        /**
         * Not needed since 1.5
         * You can identify the carrier by the id_reference
        */
    }

    public function hookDisplayOrderConfirmation()
    {
        /* Place your code here. */
    }
}
