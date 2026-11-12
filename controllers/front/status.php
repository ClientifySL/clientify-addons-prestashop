<?php

class ClientifyStatusModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $results_global = Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "configuration_clientify");
        include_once(__DIR__ . '/../admin/controller_clientify_plugin_core.php');
        $customApi = new AdminCustomClientifyEndPoint();

        $storeKey = AdminCustomClientifyEndPoint::getRequestStoreKey();

        if ($this->module->active) {
            if ($customApi->isValidStoreKey($storeKey)) {
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
                if (class_exists('AdminClientifyController')) {
                    AdminClientifyController::addLog('error', 'Fallo de autenticacion en endpoint status: storekey no coincide');
                }
                $response = array('Error' => 'Auth Error', 'data' => 'authentication code not found');
                die($customApi->Data_response($response, 401));
            }
        }
    }
}
