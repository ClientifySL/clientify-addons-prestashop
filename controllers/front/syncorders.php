<?php

class ClientifySyncordersModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $results_global = Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "configuration_clientify");
        include_once(__DIR__ . '/../admin/controller_clientify_plugin_core.php');
        $customApi = new AdminCustomClientifyEndPoint();

        $storeKey = '';
        if (isset($_SERVER['HTTP_STOREKEY'])) {
            $storeKey = strtolower($_SERVER['HTTP_STOREKEY']);
        } else {
            include_once(__DIR__ . '/getHeaders.php');
            $auth_dat = getallheaders();
            if (isset($auth_dat['storekey'])) {
                $storeKey = strtolower($auth_dat['storekey']);
            } elseif (isset($auth_dat['Storekey'])) {
                $storeKey = strtolower($auth_dat['Storekey']);
            }
        }

        if ($this->module->active) {
            if ($storeKey === $results_global[0]['clientify_store_key']) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'GET':
                        if (Tools::getValue('created_at_min')) {
                            $from = Tools::getValue('created_at_min');
                            $end = empty(Tools::getValue('date_end')) ? date("Y-m-d") : Tools::getValue('date_end');
                            $per_page = empty(Tools::getValue('per_page')) ? 0 : Tools::getValue('per_page');
                            $page = empty(Tools::getValue('page')) ? 1 : Tools::getValue('page');
                        } else {
                            $from = date("Y-m-d");
                            $end = date("Y-m-d");
                            $per_page = 0;
                            $page = 0;
                        }

                        $params = array(
                            'date_init' => $from,
                            'date_end' => $end,
                            'per_page' => $per_page,
                            'page' => $page
                        );
                        $response = $customApi->sync_all_orders($params);
                        die($customApi->Data_response($response, 200));
                        break;
                    case 'POST':
                    case 'PATCH':
                    case 'PUT':
                    case 'DELETE':
                        $r = array('message' => ' Not available');
                        die($customApi->Data_response($r, 405));
                        break;
                }
            } else {
                $response = array('Error' => 'Auth Error', 'data' => 'authentication code not found');
                die($customApi->Data_response($response, 401));
            }
        }
    }
}
