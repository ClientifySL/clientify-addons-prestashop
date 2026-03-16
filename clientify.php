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
$tabla = false;
$tables = Db::getInstance()->executeS("SHOW TABLES");
foreach ($tables as $row) {
    if (in_array(_DB_PREFIX_ . 'configuration_clientify', $row)) {
        $tabla = true;
        break;
    }
}
if (!$tabla) {
    $sql = "CREATE TABLE " . _DB_PREFIX_ . "configuration_clientify (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_shop` VARCHAR(255) Default NULL,
                `clientify_api_key` VARCHAR(255) Default NULL,
                `clientify_script` VARCHAR(255) Default NULL,
                `clientify_order_status` VARCHAR(255) Default NULL,
                `clientify_cart_hour` VARCHAR(255) Default NULL,
                `clientify_store_key` VARCHAR(255) Default NULL,
                `clientify_module_status` VARCHAR(255) Default NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
    Db::getInstance()->execute($sql);
    $data = array(
        'clientify_module_status' => 0,
        'clientify_order_status' => '2,11',
    );
    Db::getInstance()->insert('configuration_clientify', $data);
}


include_once(__DIR__ . '/controllers/admin/Api.php');

class Clientify extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'clientify';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Clientify SL';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Clientify-Ecommerce');
        $this->description = $this->l('Conecta Prestashop con Clientify para automatizar el marketing de tu tienda online.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall clientify module?');

        // Compatible PrestaShop 1.7, 8 y 9
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => '9.9.9');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);
        Configuration::updateValue('CLIENTIFY_LIVE_MODE', false);

        Configuration::updateValue('CLIENTIFY_API_KEY', 'CLIENTIFY API KEY');
        Configuration::updateValue('CLIENTIFY_SCRIPT', '', true);
        //Configuration::updateValue('.', 2);
        Configuration::updateValue('CLIENTIFY_CART_HOUR', 'TIME ABANDONED CARS');
        Configuration::updateValue('CLIENTIFY_STORE_KEY', 'STORE KEY');
        Configuration::updateValue('CLIENTIFY_id_shop', '');
        Configuration::updateValue('CLIENTIFY_STATUS', 'STORE STATUS ACTIVATE OR DEACTIVATE');

        include(__DIR__ . '/sql/install.php');


        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') && $this->registerHook('actionObjectCustomerAddAfter') && $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('displayfooter') && $this->installDB() && $this->installModuleTab() &&
            $this->registerHook('actionObjectProductAddAfter') && $this->registerHook('actionObjectProductUpdateAfter') && $this->registerHook('ModuleRoutes');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CLIENTIFY_LIVE_MODE');

        $sql_clean = "DROP TABLE IF EXISTS " . _DB_PREFIX_ . "configuration_clientify";
        Db::getInstance()->execute($sql_clean);

        include(__DIR__ . '/sql/uninstall.php');

        return $this->uninstallModuleTab() && parent::uninstall();
    }

    //Add item module
    public function installModuleTab()
    {

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminClientify';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = "Clientify";
        }
        $tab->module = $this->name;
        $tab->id_parent = 2;
        $tab->icon = "donut_large";

        return $tab->save();
    }
    public function uninstallModuleTab()
    {

        $id_tab = Tab::getIdFromClassName('AdminClientify');

        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }

        return true;
    }
    //END Add item module


    /**
     * Load the configuration form
     */
    public function getContent()
    {
        // /**
        //  * If values have been submitted in the form, process.
        //  */
        if (((bool) Tools::isSubmit('submitClientifyModule')) == true) {
            $this->postProcess();
        }

        //$this->context->smarty->assign('module_dir', $this->_path);

        //$output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/adminclientify.tpl');

        if (Tools::getValue('configure') == $this->name) {
            Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminClientify') . '&token=' . Tools::getAdminTokenLite('AdminClientify'));
        }

        //return $output . $this->renderForm();
    }

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
        $helper->submit_action = 'submitClientifyModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            /* Add values for your inputs */
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
                        'name' => 'CLIENTIFY_LIVE_MODE',
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
                        'name' => 'CLIENTIFY_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'CLIENTIFY_ACCOUNT_PASSWORD',
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
            'CLIENTIFY_LIVE_MODE' => Configuration::get('CLIENTIFY_LIVE_MODE', true),
            'CLIENTIFY_ACCOUNT_EMAIL' => Configuration::get('CLIENTIFY_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'CLIENTIFY_ACCOUNT_PASSWORD' => Configuration::get('CLIENTIFY_ACCOUNT_PASSWORD', null),
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
    // /**
    //  * Add the CSS & JavaScript files you want to be added on the FO.
    //  */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function installDB()
    {

        $tabla = false;
        $tables = Db::getInstance()->executeS("SHOW TABLES");
        foreach ($tables as $row) {
            if (in_array(_DB_PREFIX_ . 'configuration_clientify', $row)) {
                $tabla = true;
                break;
            }
        }
        if ($tabla) {
            $query = "SELECT id FROM " . _DB_PREFIX_ . "configuration_clientify";
            $result = Db::getInstance()->getRow($query);
            if ($result) {
                $id_row = $result['id']; // Reemplaza 'id' con el nombre de la columna que contiene el identificador único del registro
                $data = array(
                    'clientify_module_status' => 0,
                    'id_shop' => $this->context->shop->id,
                );
                Db::getInstance()->update('configuration_clientify', $data, 'id=' . $id_row);
            } else {
                $data = array(
                    'clientify_module_status' => 0,
                    'id_shop' => $this->context->shop->id,
                );
                Db::getInstance()->insert('configuration_clientify', $data);
            }
        } else {

            $sql = "CREATE TABLE " . _DB_PREFIX_ . "configuration_clientify (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_shop` VARCHAR(255) Default NULL,
                `clientify_api_key` VARCHAR(255) Default NULL,
                `clientify_script` VARCHAR(255) Default NULL,
                `clientify_order_status` VARCHAR(255) Default NULL,
                `clientify_cart_hour` VARCHAR(255) Default NULL,
                `clientify_store_key` VARCHAR(255) Default NULL,
                `clientify_module_status` VARCHAR(255) Default NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

            Db::getInstance()->execute($sql);
            $data = array(
                'clientify_module_status' => 0,
                'id_shop' => $this->context->shop->id,
                'clientify_order_status' => '2,11',
            );
            Db::getInstance()->insert('configuration_clientify', $data);
        }

        return true;
    }


    public function hookModuleRoutes()
    {
        return [
            'module-clientify-orders' => [
                'rule' => 'clientify/orders',
                'keywords' => [],
                'controller' => 'orders',
                'params' => [
                    'fc' => 'module',
                    'module' => 'clientify',
                ]
            ],
            'module-clientify-products' => [
                'rule' => 'clientify/products',
                'keywords' => [],
                'controller' => 'products',
                'params' => [
                    'fc' => 'module',
                    'module' => 'clientify',
                ]
            ],
            'module-clientify-contacts' => [
                'rule' => 'clientify/contacts',
                'keywords' => [],
                'controller' => 'contacts',
                'params' => [
                    'fc' => 'module',
                    'module' => 'clientify',
                ]
            ],
            'module-clientify-abandoned' => [
                'rule' => 'clientify/abandoned',
                'keywords' => [],
                'controller' => 'abandoned',
                'params' => [
                    'fc' => 'module',
                    'module' => 'clientify',
                ]
            ],
            'module-clientify-scripts' => [
                'rule' => 'clientify/scripts',
                'keywords' => [],
                'controller' => 'scripts',
                'params' => [
                    'fc' => 'module',
                    'module' => 'clientify',
                ]
            ],
            'module-clientify-connect' => [
                'rule' => 'clientify/connect',
                'keywords' => [],
                'controller' => 'connect',
                'params' => [
                    'fc' => 'module',
                    'module' => 'clientify',
                ]
            ],
            'module-clientify-syncabandoned' => [
                'rule' => 'clientify/sync_abandoned',
                'keywords' => [],
                'controller' => 'syncabandoned',
                'params' => [
                    'fc' => 'module',
                    'module' => 'clientify',
                ]
            ],
            'module-clientify-syncorders' => [
                'rule' => 'clientify/sync_orders',
                'keywords' => [],
                'controller' => 'syncorders',
                'params' => [
                    'fc' => 'module',
                    'module' => 'clientify',
                ]
            ],
            'module-clientify-synccontacts' => [
                'rule' => 'clientify/sync_contacts',
                'keywords' => [],
                'controller' => 'synccontacts',
                'params' => [
                    'fc' => 'module',
                    'module' => 'clientify',
                ]
            ],
            'module-clientify-getdata' => [
                'rule' => 'clientify/get_data',
                'keywords' => [],
                'controller' => 'getdata',
                'params' => [
                    'fc' => 'module',
                    'module' => 'clientify',
                ]
            ],
        ];
    }


    public function hookactionObjectCustomerAddAfter($params)
    {
        if (!isset($params['object']) || empty($params['object']->id)) {
            return;
        }

        include_once(__DIR__ . '/controllers/admin/controller_clientify_plugin_core.php');
        $hook_customer = new AdminCustomClientifyEndPoint();

        $customer_id = (int) $params['object']->id;
        $id_shop = Context::getContext()->shop->id;

        if (Shop::isFeatureActive() == true && $id_shop == $hook_customer->data_config['id_shop'] && $hook_customer->data_config['clientify_module_status'] == 1) {
            $api = new ClientifyApi();
            $new_custumer = $hook_customer->get_contact($customer_id);
            if ($new_custumer) {
                $new_custumer['status'] = 'customer';
                $new_custumer['store_url'] = $hook_customer->GetApiUrl($hook_customer->data_config['id_shop']);
                $api->Post_Contacts_Clientify($new_custumer);
            }
        } elseif (Shop::isFeatureActive() == false && $hook_customer->data_config['clientify_module_status'] == 1) {
            $api = new ClientifyApi();
            $new_custumer = $hook_customer->get_contact($customer_id);
            if ($new_custumer) {
                $new_custumer['status'] = 'customer';
                $new_custumer['store_url'] = $hook_customer->GetApiUrl($hook_customer->data_config['id_shop']);
                $api->Post_Contacts_Clientify($new_custumer);
            }
        }
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        if (!isset($params['id_order']) || empty($params['id_order'])) {
            return;
        }

        include_once(__DIR__ . '/controllers/admin/controller_clientify_plugin_core.php');
        $hook_customer = new AdminCustomClientifyEndPoint();

        $id_order = (int) $params['id_order'];
        $order = new Order($id_order);

        if (!Validate::isLoadedObject($order)) {
            return;
        }

        if (Shop::isFeatureActive() == true && $order->id_shop == $hook_customer->data_config['id_shop'] && $hook_customer->data_config['clientify_module_status'] == 1) {
            $hook_customer->get_orders($id_order);
        } elseif (Shop::isFeatureActive() == false && $hook_customer->data_config['clientify_module_status'] == 1) {
            $hook_customer->get_orders($id_order);
        }
    }

    public function hookDisplayFooter($params)
    {
        include_once(__DIR__ . '/controllers/admin/controller_clientify_plugin_core.php');
        $hook_customer = new AdminCustomClientifyEndPoint();
        $html = "";
        if ($hook_customer->data_config['clientify_script'] != '' && $hook_customer->data_config['clientify_module_status'] == 1) {
            $bottom_script = $hook_customer->data_config['clientify_script'];
            $html = '<script src=' . $bottom_script . '></script>';
        }


        return $html;
    }
    public function hookActionObjectProductAddAfter($params)
    {
        if (!isset($params['object']) || empty($params['object']->id)) {
            return true;
        }

        // if (shop::isFeatureActive() == true && $params['object']->id_shop_default == Configuration::get('CLIENTIFY_id_shop')){
        //     $hook_customer = new AdminCustomClientifyEndPoint();
        //     $hook_customer->get_product((int)($params['object']->id));
        // }
        return true;
    }

    public function hookActionObjectProductUpdateAfter($params)
    {
        if (!isset($params['object']) || empty($params['object']->id)) {
            return true;
        }

        include_once(__DIR__ . '/controllers/admin/controller_clientify_plugin_core.php');

        $hook_customer = new AdminCustomClientifyEndPoint();
        if (Context::getContext()->shop->id == $hook_customer->data_config['id_shop'] && $hook_customer->data_config['clientify_module_status'] == 1) {
            $hook_customer->get_product((int) ($params['object']->id));
        }
        return true;
    }
}