<?php

class ClientifyStatusModuleFrontController extends ModuleFrontController
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
                        $params = array(
                            'hook' => Tools::getValue('hook'),
                        );
                        $response = $customApi->get_plugin_status($params);
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
