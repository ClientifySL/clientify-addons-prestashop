<?php

use GuzzleHttp\Message\Request;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_VERSION_')) {
    exit;
}
include_once(__DIR__ . '/../../clientify.php');
include_once(__DIR__ . '/Api.php');
include_once(__DIR__ . '/controller_clientify_plugin_core.php');

$action = Tools::getValue('action');
$customApi = new AdminCustomClientifyEndPoint();

    switch ($action) {
        case 'get_data': {
            $customApi->get_contact(
                Tools::getValue('email')
            );
            break;
        }
    }

class AdminClientifyController extends ModuleAdminController
{

    public function __construct()
    {
        parent::__construct();
        $this->context = Context::getContext();
        $this->bootstrap = false; 
    }

    public function initContent()
    {
        parent::initContent();
        $this->assign();
    
    }

    public function hookactionCustomerAccountAdd  (){
		
      
        }

    public function assign()
    {       
        $endpoint_class = new AdminCustomClientifyEndPoint(); 
        $module = new Clientify();
        $shop = new Shop((int)$this->context->shop->id);
        $order_status = new OrderReturnState(1);

      
        //$_GET['tab'] = 'logs';
        $clientify_api = Configuration::get('CLIENTIFY_MODULE_API');
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';

        $get_data = "SELECT * FROM "._DB_PREFIX_."configuration_clientify";
        $results = Db::getInstance()->executeS($get_data);
        $base_url = $endpoint_class->GetApiUrl($results[0]['id_shop']);//$shop->getBaseURL();
         // In PS 8/9, Tools::encrypt does not exist; JS already uses clientifyController for AJAX,
        // so we don't need to build this legacy URL.
        $ajax = null;
        // Get the store name in a PS 8/9 compatible format
        $shop_name = 0;
        if (!empty($results[0]['id_shop'])) {
            $shop_obj = new Shop((int) $results[0]['id_shop']);
            if ($shop_obj->id) {
                $shop_name = $shop_obj->name;
            }
        }
        $adminController = $this->context->link->getAdminLink('AdminClientify');

        $this->context->smarty->assign(array(
            'clientifyController' => $adminController,
            'tab' => $tab,
            'url_base' => $base_url,
            'orderstatus' => $this->getOrdersSatus(),
            'shops' => Shop::getShops(),
            'data_config' => $results[0],
            'shop_config' => $shop_name,
            // Ruta base del módulo (para JS/CSS/IMG en PS 8/9)
            'module_dir' => $module->getPathUri(),
        ));
        $this->setTemplate('adminclientify.tpl');

    }


    public function ajaxProcessconnectClientify()
    {   
        $db = Db::getInstance();
        //$order_sta = implode(',',Tools::getValue('order_stat'));
        $order_stat_values = Tools::getValue('order_stat');
        $order_sta = !empty($order_stat_values) ? implode(',', $order_stat_values) : '';
        $ac_time = Tools::getValue('ac_time');
        $id_shop = Tools::getValue('id_shop');

        $get_data = "SELECT * FROM "._DB_PREFIX_."configuration_clientify";
        $results = Db::getInstance()->executeS($get_data);
        
        $key = Tools::getValue('apikey') ;


        if (Tools::getValue('apikey') != '' || $results[0]['clientify_api_key'] != '') {
            /* generate uid key for connect to clientify */
            $endpoint_class = new AdminCustomClientifyEndPoint();
            $api = new ClientifyApi;
            $key_uid = $endpoint_class->token_id();
            $url_base = $endpoint_class->GetApiUrl($id_shop);

            $shop_obj = new Shop((int) $id_shop);
            $shop_name = $shop_obj->id ? $shop_obj->name : 'prestashop';

            $post_key = array(
                'ecommerce' => 'prestashop',
                'action'    => 'connect',
                'store_key' => $key_uid,
                'name'      => $shop_name,
                'store_url' => $url_base
            );
            $response = $api->Post_Base_Clientify($post_key, $key);  
        };

        if (is_null($response) || isset($response->detail)) {

            $data= is_null($response) !=    '' ? "error" : $response->detail;
            
            
            $response = array(
                'data' => array(
                        'status' => $data,
                    )
                );

                $data_config = array(
                    'id_shop' => $id_shop,
                    'clientify_api_key' => $key,
                    //'clientify_script' => ,
                    'clientify_order_status' => $order_sta,
                    'clientify_cart_hour' => $ac_time,
                    'clientify_store_key' => $key_uid,
                    'clientify_module_status' => 0
                );
                $db->update('configuration_clientify', $data_config);
            
        }else {
            foreach ($response as $obj) {
                $status = $obj->status;
            }     
            if ($status == 'success') {
                $data_config = array(
                    'id_shop' => $id_shop,
                    'clientify_api_key' => $key,
                    //'clientify_script' => ,
                    'clientify_order_status' => $order_sta,
                    'clientify_cart_hour' => $ac_time,
                    'clientify_store_key' => $key_uid,
                    'clientify_module_status' => 1
                );
                $db->update('configuration_clientify', $data_config);
    
                // Configuration::updateValue('CLIENTIFY_API_KEY', $key);
                // Configuration::updateValue('CLIENTIFY_STORE_KEY', $key_uid);
                // Configuration::updateValue('CLIENTIFY_CART_HOUR', $ac_time);
                // Configuration::updateValue('CLIENTIFY_ORDER_STATUS', $order_sta);
                // //Configuration::updateValue('CLIENTIFY_id_shop', $id_shop);
                // Configuration::updateValue('CLIENTIFY_STATUS', 1);
            }
            
        }
        
        exit(json_encode($response));        
    }


    public function ajaxProcessdisconnectClientify()
    {   
        $db = Db::getInstance();
        $get_data = "SELECT * FROM "._DB_PREFIX_."configuration_clientify";
        $results = Db::getInstance()->executeS($get_data);
        $key = Tools::getValue('apikey') !=    '' ? Tools::getValue('apikey') : $results[0]['clientify_store_key'];

		if (Tools::getValue('apikey') != '' || $results[0]['clientify_api_key'] != '') {
			/* generate uid key for connect to clientify */
            $endpoint_class = new AdminCustomClientifyEndPoint();
            $api = new ClientifyApi;

            $key_uid = $results[0]['clientify_store_key'];
			$url_base = $endpoint_class->GetApiUrl($results[0]['id_shop']);

            $shop_obj = new Shop((int) $results[0]['id_shop']);
            $shop_name = $shop_obj->id ? $shop_obj->name : 'prestashop';

			$post_key = array(
				'ecommerce' => 'prestashop',
				'action'    => 'disconnect',
				'store_key' => $key_uid,
				'name'      => $shop_name,
				'store_url' => $url_base
			);

			$response = $api->Post_Base_Clientify($post_key, $key);
		}
        if (is_null($response) || isset($response->detail)) {

            

            $data_config = array(
                'clientify_script' => null,
                'clientify_cart_hour' => null,
                'clientify_module_status' => 0
            );
            $db->update('configuration_clientify', $data_config);
            $data= is_null($response) !=    '' ? "error" : $response->detail;

            $response = array(
                'data' => array(
                        'status' => $data
                    )
                );
            
        }else {
            foreach ($response as $obj) {
                $status = $obj->status;
            }     
            if ($status == 'success' || $status == 'failed' || $status == 'error' ) {

                $data_config = array(
                    'clientify_script' => null,
                    'clientify_cart_hour' => null,
                    'clientify_module_status' => 0
                );
                $db->update('configuration_clientify', $data_config);
            }
        }
        
        exit(json_encode($response)); 
    }

    public function setYouTubeUrl($id_product, $youtube_url)
    {

        $youtube_url = ($youtube_url != 'undefined') ? $youtube_url : '';

        $update = "UPDATE " . _DB_PREFIX_ . "product
                  SET youtube_url = '$youtube_url' 
                WHERE id_product = $id_product";


        return Db::getInstance()->ExecuteS($update);
    }



    function getOrdersSatus(){
        $sql = "SELECT id_order_state,name FROM " . _DB_PREFIX_ . "order_state_lang";
        $result = Db::getInstance()->executeS($sql);
  
        return $result;
    }

    

}
