<?php
require_once(__DIR__ . '/../../config/config.inc.php');
require_once(__DIR__ . '/../../init.php');
include_once(__DIR__ . '/controllers/admin/controller_clientify_plugin_core.php');
$results_global = Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "configuration_clientify");
$action = Tools::getValue('action');
$action_clean = explode("?", $action);
$customApi = new AdminCustomClientifyEndPoint();
$auth_dat = getallheaders();
$key = $results_global[0]['clientify_store_key'];
$module = Module::getInstanceByName('clientify');
if ($module->active) {

    if (isset($auth_dat['storekey'])) {
        $storeKey = ucfirst(strtolower($auth_dat['storekey']));
    } elseif (isset($auth_dat['Storekey'])) {
        $storeKey = ucfirst(strtolower($auth_dat['Storekey']));
    } else {
        $storeKey = 0;
    }
    if ($storeKey == $key) {
        switch ($action_clean[0]) {
            case 'order': {
                    $request_order_id = 42;
                    $response = $customApi->get_orders($request_order_id);
                    return Data_response($response, 200);
                    break;
                }
            case '/orders': {
                    ;
                    if (isset($action_clean[1]) && $action_clean[1] != 'created_at_min') {
                        $f_inin = explode("=", $action_clean[1]);
                        $from = $f_inin[1];
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
                    $r = $customApi->get_all_orders($params);
                    return Data_response($r, 200);
                    break;
                }
            case '/contacts': {

                    if (!empty($action_clean[1]) || $action_clean[1] != 'created_at_min') {
                        $f_inin = explode("=", $action_clean[1]);
                        $from = $f_inin[1];
                        $end = empty(Tools::getValue('date_end')) ? date("Y-m-d") : Tools::getValue('date_end');
                        $per_page = empty(Tools::getValue('per_page')) ? 0 : Tools::getValue('per_page');
                        $page = empty(Tools::getValue('page')) ? 1 : Tools::getValue('page');
                    } else {
                        $from = 0;
                        $end = 0;
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
                    return Data_response($response, 200);
                    break;
                }
            case '/products': {

                    if (!empty($action_clean[1]) || $action_clean[1] != 'created_at_min') {
                        $f_inin = explode("=", $action_clean[1]);
                        $from = $f_inin[1];
                        $end = empty(Tools::getValue('date_end')) ? date("Y-m-d") : Tools::getValue('date_end');
                        $per_page = empty(Tools::getValue('per_page')) ? 0 : Tools::getValue('per_page');
                        $page = empty(Tools::getValue('page')) ? 1 : Tools::getValue('page');
                    } else {
                        $from = 0;
                        $end = 0;
                        $per_page = 0;
                        $page = 0;
                    }
                    $params = array(
                        'date_init' => $from,
                        'date_end' => $end,
                        'per_page' => $per_page,
                        'page' => $page
                    );
                    $response = $customApi->get_all_products($params);
                    return Data_response($response, 200);
                    break;
                }
            case '/abandoned': {
                    if (!empty($action_clean[1]) || $action_clean[1] != 'created_at_min') {
                        $f_inin = explode("=", $action_clean[1]);
                        $from = $f_inin[1];
                        $end = empty(Tools::getValue('date_end')) ? date("Y-m-d") : Tools::getValue('date_end');
                        $per_page = empty(Tools::getValue('per_page')) ? 0 : Tools::getValue('per_page');
                        $page = empty(Tools::getValue('page')) ? 1 : Tools::getValue('page');
                    } else {
                        $from = 0;
                        $end = 0;
                        $per_page = 0;
                        $page = 0;
                    }

                    $params = array(
                        'date_init' => $from,
                        'date_end' => $end,
                        'per_page' => $per_page,
                        'page' => $page
                    );

                    $r = $customApi->get_abandoned_carts($params);
                    return Data_response($r, 200);
                    break;
                }
            case '/scripts': {
                    $json = file_get_contents('php://input');
                    $data = json_decode($json, true);
                    if (!empty($action_clean[0])) {
                        //$set_script = explode("=",$action_clean[1]);                    
                        if (!empty($data['set_script'])) {


                            $data_config = array(
                                'clientify_script' => $data['set_script']
                            );
                            $result = Db::getInstance()->update('configuration_clientify', $data_config);
                            $get_script = Db::getInstance()->executeS("SELECT clientify_script FROM " . _DB_PREFIX_ . "configuration_clientify");

                            if ($get_script[0]['clientify_script'] == $data['set_script']) {
                                $r = array('message' => 'success');
                                return Data_response($r, 200);
                            } else {
                                $r = array('message' => 'Error Script');
                                return Data_response($r, 500);
                            }
                        } else {
                            $r = array('message' => 'Error Script not Found');
                            return Data_response($r, 404);
                        }

                    } else {
                        $r = array('message' => 'Error Request');
                        return Data_response($r, 400);
                    }
                    break;
                }
            case '/connect': {
                    $action_handling = explode("=", $action_clean[1]);
                    $params = array('action' => $action_handling[1]);
                    $response = $customApi->plugin_handling($params);
                    return Data_response($response, 200);
                    break;
                }
        }
    } else {
        $response = array('Error' => 'Auth Error', 'data' => 'authentication code not found');
        return Data_response($response, 500);
    }
}

function Data_response($data, $httpStatus)
{
    header_remove();
    if (isset($data['Link'])) {
        header('Content-Type: application/json; charset=utf-8');
        $data['Link'] == '' ? '' : header('Link:' . $data['Link']);
        http_response_code($httpStatus);
        echo json_encode($data['data']);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($httpStatus);
        echo json_encode($data);
    }
    exit();
}