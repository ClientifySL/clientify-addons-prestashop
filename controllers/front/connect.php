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
$module = Module::getInstanceByName('clientify');
if ($module->active) {
    if (isset($auth_dat['storekey'])) {
        $storeKey = strtolower($auth_dat['storekey']);
    } elseif (isset($auth_dat['Storekey'])) {
        $storeKey = strtolower($auth_dat['Storekey']);
    } else {
        $storeKey = 0;
    }
        switch ($_SERVER['REQUEST_METHOD']) {

            case 'GET':
                $r = array('message' => ' Not available');
                return $customApi->Data_response($r, 200);
            case 'POST':
                if (Tools::getValue('action')) {
                    $params = array('action' => Tools::getValue('action'));
                    $response = $customApi->plugin_handling($params);
                    return $customApi->Data_response($response, 200);

                } else {
                    $params = array('action' => Tools::getValue('action'));
                    return $customApi->Data_response($response, 500);
                }
            case 'PATCH': // you can also separate these into their own methods
            case 'PUT':
                $r = array('message' => ' Not available');
                return $customApi->Data_response($r, 405);
            case 'DELETE':
                $r = array('message' => ' Not available');
                return $customApi->Data_response($r, 405);
            default:
            // throw some error or whatever
        }

}


?>