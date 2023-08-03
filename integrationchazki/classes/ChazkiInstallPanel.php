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
class ChazkiInstallPanel
{
    const MODULE_API_KEY_NAME = 'CHAZKI_API_KEY';
    const MODULE_BRANCH_ID_NAME = 'CHAZKI_BRANCH_ID';
    const MODULE_SERVICE_NAME = 'CHAZKI_SERVICE';
    const MODULE_STATUS_NAME = 'CHAZKI_STATUS';
    const IS_API_SETTINGS_TAB = 'IS_CHAZKI_API_SET';
    const IS_ORDER_SETTINGS_TAB = 'IS_CHAZKI_ORDER_SET';
    const MODULE_NAME = 'IntegrationChazkiModule';

    public $name = 'integrationchazki';

    public static $services_types = [
        [
            'id' => 'SAME_DAY',
            'name' => 'Same Day',
        ],
        [
            'id' => 'NEXT_DAY',
            'name' => 'Next Day',
        ],
        [
            'id' => 'EXPRESS',
            'name' => 'Express',
        ],
    ];

    public static $status_types = [
        [
            'id' => 'NEW',
            'name' => 'Nuevo',
        ],
        [
            'id' => 'PAYMENT',
            'name' => 'Pago aceptado',
        ],
    ];

    public function __construct($module)
    {
        $this->module = $module;
        $this->context = Context::getContext();
        $this->webhook = new ChazkiWebhooks($module);
    }

    public function getContent()
    {
        $output = '';

        if (((bool) Tools::isSubmit('submit' . self::MODULE_NAME)) == true) {
            if (Tools::getValue(self::IS_API_SETTINGS_TAB)) {
                $api_key = trim((string) Tools::getValue(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_API_KEY_NAME)));
                $service_id = trim((string) Tools::getValue(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_SERVICE_NAME)));
                if ($api_key) {
                    ChazkiHelper::updateValue(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_API_KEY_NAME), $api_key);
                    ChazkiHelper::updateValue(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_SERVICE_NAME), $service_id);
                    $this->webhook->saveConfig($api_key);
                    $this->webhook->updateBody($api_key);
                } else {
                    ChazkiHelper::setNotification(
                        $this->module->l('The entered API key is not accepted Spring GDS, contact your manager'),
                        ChazkiHelper::MSG_TYPE_ERROR
                    );
                }
            } elseif (Tools::getValue(self::IS_ORDER_SETTINGS_TAB)) {
                $send_status = trim((string) Tools::getValue(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_STATUS_NAME)));
                $branch_id = trim((string) Tools::getValue(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_BRANCH_ID_NAME)));
                ChazkiHelper::updateValue(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_STATUS_NAME), $send_status);
                ChazkiHelper::updateValue(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_BRANCH_ID_NAME), $branch_id);

                ChazkiHelper::setNotification($this->module->l('Customs information updated'));
            }

            /*Tools::redirectAdmin(
                $this->context->link->getAdminLink('AdminModules', true) .
                '&configure=' . $this->module->name
            );*/
        }

        $api_id = ChazkiHelper::get(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_API_KEY_NAME));

        if ($api_id) {
            $this->getSettings();
        }

        $output .= $this->displaySettingsApiForm();
        $output .= $this->displaySettingsOrderForm();

        return $output;
    }

    /**
     * Get settings by API and save
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function getSettings()
    {
        return true;
    }

    /**
     * Form API key
     *
     * @return string HTML for the bo page
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function displaySettingsApiForm()
    {
        // Get default language
        // $default_lang = (int)ChazkiHelper::get('PS_LANG_DEFAULT');
        $api_key = ChazkiHelper::get(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_API_KEY_NAME));
        $service_id = ChazkiHelper::get(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_SERVICE_NAME));
        $api_key_msg = $this->module->l('Puedes conseguir Enterprise Key desde el dashboard para clientes de Chazki');

        // Init Fields form array
        $fields_form = $this->getConfigForm($api_key_msg);

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->module->name;

        // Language
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        // Title and toolbar
        $helper->title = $this->module->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . self::MODULE_NAME;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->module->l('Save'),
                'href' => AdminController::$currentIndex .
                '&configure=' . $this->module->name .
                '&save' . $this->module->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->module->l('Back to list'),
            ],
        ];

        // Load current value
        $helper->fields_value[Tools::strtoupper(self::IS_API_SETTINGS_TAB)] = 1;
        $helper->fields_value[Tools::strtoupper(_DB_PREFIX_ . self::MODULE_API_KEY_NAME)] = $api_key;
        $helper->fields_value[Tools::strtoupper(_DB_PREFIX_ . self::MODULE_SERVICE_NAME)] = $service_id;

        return $helper->generateForm($fields_form);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm($api_key_msg)
    {
        return [
            [
                'form' => [
                    'legend' => [
                        'title' => $this->module->l('Configuración de Api'),
                        'icon' => 'icon-cogs',
                    ],
                    'description' => $api_key_msg,
                    'input' => [
                        [
                            'type' => 'hidden',
                            'name' => self::IS_API_SETTINGS_TAB,
                        ],
                        [
                            'type' => 'text',
                            'name' => Tools::strtoupper(_DB_PREFIX_ . self::MODULE_API_KEY_NAME),
                            'label' => $this->module->l('Enterprise Key'),
                            'maxlength' => 255,
                            'required' => true,
                        ],
                        [
                            'type' => 'select',
                            'name' => Tools::strtoupper(_DB_PREFIX_ . self::MODULE_SERVICE_NAME),
                            'id' => Tools::strtoupper(_DB_PREFIX_ . self::MODULE_SERVICE_NAME),
                            'label' => $this->module->l('Servicio'),
                            'options' => [
                                'query' => self::$services_types,
                                'id' => 'id',
                                'name' => 'name',
                            ],
                            'required' => true,
                        ],
                    ],
                    'submit' => [
                        'title' => $this->module->l('Save'),
                        'class' => 'btn btn-default pull-right',
                    ],
                ],
            ],
        ];
    }

    protected function displaySettingsOrderForm()
    {
        $send_status = ChazkiHelper::get(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_STATUS_NAME));
        $branch_id = ChazkiHelper::get(Tools::strtoupper(_DB_PREFIX_ . self::MODULE_BRANCH_ID_NAME));
        // Init Fields form array
        $fields_form = $this->getConfigOrderForm();

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->module->name;

        // Language
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        // Title and toolbar
        $helper->title = $this->module->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . self::MODULE_NAME;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->module->l('Save'),
                'href' => AdminController::$currentIndex .
                '&configure=' . $this->module->name .
                '&save' . $this->module->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->module->l('Back to list'),
            ],
        ];

        // Load current value
        $helper->fields_value[self::IS_ORDER_SETTINGS_TAB] = 1;
        $helper->fields_value[Tools::strtoupper(_DB_PREFIX_ . self::MODULE_STATUS_NAME)] = $send_status;
        $helper->fields_value[Tools::strtoupper(_DB_PREFIX_ . self::MODULE_BRANCH_ID_NAME)] = $branch_id;

        return $helper->generateForm($fields_form);
    }

    protected function getConfigOrderForm()
    {
        return [
            [
                'form' => [
                    'legend' => [
                        'title' => $this->module->l('Configuración de Ordenes'),
                        'icon' => 'icon-cogs',
                    ],
                    'input' => [
                        [
                            'type' => 'hidden',
                            'name' => self::IS_ORDER_SETTINGS_TAB,
                        ],
                        [
                            'type' => 'text',
                            'name' => Tools::strtoupper(_DB_PREFIX_ . self::MODULE_BRANCH_ID_NAME),
                            'label' => $this->module->l('Codigo Sucursal'),
                            'maxlength' => 255,
                            'required' => false,
                        ],
                        [
                            'type' => 'select',
                            'name' => Tools::strtoupper(_DB_PREFIX_ . self::MODULE_STATUS_NAME),
                            'label' => $this->module->l('Estado de envio'),
                            'options' => [
                                'query' => self::$status_types,
                                'id' => 'id',
                                'name' => 'name',
                            ],
                            'required' => true,
                        ],
                    ],
                    'submit' => [
                        'title' => $this->module->l('Save'),
                        'class' => 'btn btn-default pull-right',
                    ],
                ],
            ],
        ];
    }
}
