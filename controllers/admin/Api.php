<?php
if (!class_exists('ClientifyApi')) {
    //include_once(__DIR__ . '/AdminClientify.php');

    class ClientifyApi
    {
        var $api_key;

        var $api_url = 'https://ecommerce.ngrok.io/ecommerce/v2/';
        //var $api_url = 'https://api.clientify.com/ecommerce/v2/';

        public function __construct()
        {
            $results_global = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."configuration_clientify");           
            $this->api_key = $results_global[0]['clientify_api_key'];
        }
        public function Post_Base_Clientify($data, $key)
        {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->api_url . 'connection_by_plugin/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
            ));
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Token ' . $key));
            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }

        public function Get_Api($end_point)
        {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->api_url . $end_point,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Token ' . $this->api_key));
            $response = curl_exec($curl);
            curl_close($curl);
            return $response;
        }

        public function Post_Contacts_Clientify($data)
        {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->api_url . 'prestashop_listener',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
            ));
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Token ' . $this->api_key));
            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }

        public function Post_Order_Clientify($data)
        {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->api_url . 'prestashop_listener',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
            ));
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Token ' . $this->api_key));
            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }


        // public function send($end_point, $data, $method = 'post')
        // {
        //     $debug_log = (bool)get_option('CLIENTIFY_API_LOG');

        //     if (empty($this->api_key)) {
        //         return false;
        //     }

        //     try {
        //         $ch = curl_init($this->api_url . $end_point);
        //         $payload = json_encode($data);
        //         curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        //         if ($method == 'post') {
        //             curl_setopt($ch, CURLOPT_POST, 1);
        //         } else {
        //             curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        //         }

        //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //         curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Token ' . $this->api_key));
        //         $response = curl_exec($ch);
        //         curl_close($ch);

        //         if ($debug_log) {
        //             $log =  '--------------------------------  REQUEST ' . date('Y-m-d H:i:s') . '  --------------------------------' . PHP_EOL;
        //             $log .= print_r($data, true);
        //             $log .= '--------------------------------  Response     --------------------------------' . PHP_EOL;
        //             $log .= $response . PHP_EOL;
        //             $log .= '----------------------------------------------------------------' . PHP_EOL;
        //             $log_path = __DIR__ . '/../logs/request-' . date('Y-m-d') . '.log';
        //             file_put_contents($log_path, $log, FILE_APPEND);
        //         }

        //         return json_decode($response);
        //     } catch (Exception $e) {
        //         if ($debug_log) {
        //             $log =  '--------------------------------  ERROR ' . date('Y-m-d H:i:s') . '  --------------------------------' . PHP_EOL;
        //             $log .= print_r($data, true);
        //             $log .= '--------------------------------  Error     --------------------------------' . PHP_EOL;
        //             $log .= $e->getMessage() . PHP_EOL;
        //             $log .= '----------------------------------------------------------------' . PHP_EOL;
        //             $log_path = __DIR__ . '/../logs/error-' . date('Y-m-d') . '.log';
        //             file_put_contents($log_path, $log, FILE_APPEND);
        //         }
        //     }
        // }
    }
}
