<?php

//require_once __DIR__ . '/../AbstractRestController.php';
require_once(__DIR__ . '/../../../../config/config.inc.php');
require_once(__DIR__ . '/../../../../init.php');
$results_global = Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "configuration_clientify");
include_once(__DIR__ . '/../admin/controller_clientify_plugin_core.php');
include_once(__DIR__ . '/../front/getHeaders.php');
$auth_dat = getallheaders();
$customApi = new AdminCustomClientifyEndPoint();
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
                $response = $customApi->get_all_contacts($params);

                return $customApi->Data_response($response, 200);

            case 'POST':
                $r = array('message' => ' Not available');
                return $customApi->Data_response($r, 405);
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
    } else {
        $response = array('Error' => 'Auth Error', 'data' => 'authentication code not found');
        return $customApi->Data_response($response, 401);
    }
}


?>