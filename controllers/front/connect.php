<?php

class ClientifyConnectModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $results_global = Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "configuration_clientify");
        include_once(__DIR__ . '/../admin/controller_clientify_plugin_core.php');
        $customApi = new AdminCustomClientifyEndPoint();

        if ($this->module->active) {
            // connect/disconnect rota y devuelve la storekey: sin esta comprobacion cualquiera
            // podia obtener una storekey valida y leer clientes y pedidos de la tienda.
            if (!$customApi->isValidStoreKey(AdminCustomClientifyEndPoint::getRequestStoreKey())) {
                if (class_exists('AdminClientifyController')) {
                    AdminClientifyController::addLog('error', 'Fallo de autenticacion en endpoint connect: storekey no coincide');
                }
                $response = array('Error' => 'Auth Error', 'data' => 'authentication code not found');
                die($customApi->Data_response($response, 401));
            }

            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $r = array('message' => ' Not available');
                    die($customApi->Data_response($r, 200));
                    break;
                case 'POST':
                    $json = Tools::file_get_contents('php://input');
                    $paramsObj = json_decode($json);

                    if (Tools::getValue('action')) {
                        $params = array('action' => Tools::getValue('action'));
                        $response = $customApi->plugin_handling($params);
                        die($customApi->Data_response($response, 200));
                    } elseif (isset($paramsObj->action)) {
                        $params = array('action' => $paramsObj->action);
                        $response = $customApi->plugin_handling($params);
                        die($customApi->Data_response($response, 200));
                    } else {
                        $r = array('message' => 'Action required');
                        die($customApi->Data_response($r, 500));
                    }
                    break;
                case 'PATCH':
                case 'PUT':
                case 'DELETE':
                    $r = array('message' => ' Not available');
                    die($customApi->Data_response($r, 405));
                    break;
            }
        }
    }
}
