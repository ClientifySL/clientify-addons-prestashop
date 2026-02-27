<?php

//require_once __DIR__ . '/../AbstractRestController.php';
require_once(__DIR__ . '/../../../../config/config.inc.php');
require_once(__DIR__ . '/../../../../init.php');
$results_global = Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "configuration_clientify");
include_once(__DIR__ . '/../admin/controller_clientify_plugin_core.php');
include_once(__DIR__ . '/../front/getHeaders.php');
$auth_dat = getallheaders();
$json = Tools::file_get_contents('php://input');
$customApi = new AdminCustomClientifyEndPoint();
$params = json_decode($json);
$jsonData = Tools::file_get_contents('php://input');
$jsonArray = json_decode($jsonData, true);
$module = Module::getInstanceByName('clientify');
if ($module->active) {
    if (isset($auth_dat['storekey'])) {
        $storeKey = strtolower($auth_dat['storekey']);
    } elseif (isset($auth_dat['Storekey'])) {
        $storeKey = strtolower($auth_dat['Storekey']);
    } else {
        $storeKey = 0;
    }
    if ($storeKey === $results_global[0]['clientify_store_key']) {
        switch ($_SERVER['REQUEST_METHOD']) {

            case 'GET':
                $get_script = Db::getInstance()->executeS("SELECT clientify_script FROM " . _DB_PREFIX_ . "configuration_clientify");
                $r = array('message' => 'success', 'script' => $get_script[0]['clientify_script']);
                return $customApi->Data_response($r, 200);
            case 'POST':
                if (!empty($params->set_script)) {
                    if (!empty($params->set_script)) {
                        $data_config = array('clientify_script' => $params->set_script);
                        $result = Db::getInstance()->update('configuration_clientify', $data_config);
                        $get_script = Db::getInstance()->executeS("SELECT clientify_script FROM " . _DB_PREFIX_ . "configuration_clientify");

                        if ($get_script[0]['clientify_script'] == $params->set_script) {
                            $r = array('message' => 'success');
                            return $customApi->Data_response($r, 200);
                        } else {
                            $r = array('message' => 'Error Script');
                            return $customApi->Data_response($r, 500);
                        }
                    } else {
                        $r = array('message' => 'Error Script not Found');
                        return $customApi->Data_response($r, 404);
                    }

                } else {
                    $r = array('message' => 'Error Request Param Script null');
                    return $customApi->Data_response($message, 400);
                }
            case 'PATCH': // you can also separate these into their own methods
            case 'PUT':
                $r = array('message' => 'Method Not Allowed');
                return $customApi->Data_response($r, 405);
            case 'DELETE':
                $data_config = array('clientify_script' => null);
                $result = Db::getInstance()->update('configuration_clientify', $data_config);
                $r = array('message' => 'Script Delete succses');
                return $customApi->Data_response($r, 200);
            default:
            // throw some error or whatever
        }
    } else {
        $response = array('Error' => 'Auth Error', 'data' => 'authentication code not found');
        return $customApi->Data_response($response, 401);
    }
}


?>