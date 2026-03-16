<?php

class ClientifyScriptsModuleFrontController extends ModuleFrontController
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
                        $get_script = Db::getInstance()->executeS("SELECT clientify_script FROM " . _DB_PREFIX_ . "configuration_clientify");
                        $r = array('message' => 'success', 'script' => $get_script[0]['clientify_script']);
                        die($customApi->Data_response($r, 200));
                        break;
                    case 'POST':
                        $json = Tools::file_get_contents('php://input');
                        $params = json_decode($json);

                        if (!empty($params->set_script)) {
                            $data_config = array('clientify_script' => $params->set_script);
                            Db::getInstance()->update('configuration_clientify', $data_config);
                            $get_script = Db::getInstance()->executeS("SELECT clientify_script FROM " . _DB_PREFIX_ . "configuration_clientify");

                            if ($get_script[0]['clientify_script'] == $params->set_script) {
                                $r = array('message' => 'success');
                                die($customApi->Data_response($r, 200));
                            } else {
                                $r = array('message' => 'Error Script');
                                die($customApi->Data_response($r, 500));
                            }
                        } else {
                            $r = array('message' => 'Error Request Param Script null');
                            die($customApi->Data_response($r, 400));
                        }
                        break;
                    case 'PATCH':
                    case 'PUT':
                        $r = array('message' => 'Method Not Allowed');
                        die($customApi->Data_response($r, 405));
                        break;
                    case 'DELETE':
                        $data_config = array('clientify_script' => null);
                        Db::getInstance()->update('configuration_clientify', $data_config);
                        $r = array('message' => 'Script Delete succses');
                        die($customApi->Data_response($r, 200));
                        break;
                    default:
                        $r = array('message' => 'Method Not Allowed');
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