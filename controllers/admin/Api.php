<?php
if (!class_exists('ClientifyApi')) {

    class ClientifyApi
    {
        const DEFAULT_API_URL = 'https://api-plus.clientify.com/api/ecommerce/v2/';

        /**
         * Los hooks (registro, checkout, guardado de producto) se ejecutan dentro de la
         * peticion del cliente o del empleado: con timeouts largos y reintentos, una API
         * lenta bloquearia la confirmacion del pedido. Los hooks activan este modo con
         * setRealtime(true); las sincronizaciones masivas mantienen timeout largo y reintentos.
         */
        private static $realtime = false;

        var $api_key;

        var $api_url;

        public function __construct()
        {
            $results_global = Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "configuration_clientify");
            $this->api_key = !empty($results_global[0]['clientify_api_key']) ? $results_global[0]['clientify_api_key'] : '';
            $this->api_url = self::getApiUrl();
        }

        public static function setRealtime($realtime)
        {
            self::$realtime = (bool) $realtime;
        }

        /**
         * URL base de la API. Se puede sobrescribir para entornos de pruebas con
         * Configuration::updateValue('CLIENTIFY_API_URL', 'https://.../api/ecommerce/v2/').
         */
        public static function getApiUrl()
        {
            $custom = trim((string) Configuration::get('CLIENTIFY_API_URL'));
            if ($custom !== '' && preg_match('#^https?://#i', $custom)) {
                return rtrim($custom, '/') . '/';
            }
            return self::DEFAULT_API_URL;
        }

        private function makeCurlRequest($url, $method = 'GET', $data = null, $headers = array())
        {
            if (self::$realtime) {
                $maxRetries = 1;
                $timeout = 10;
            } else {
                $maxRetries = 3;
                $timeout = 60;
            }
            $retryDelay = 1;
            $errorMsg = '';
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => $method,
                ));
                if ($data && in_array($method, array('POST', 'PUT', 'PATCH'))) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                }
                if ($headers) {
                    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                }
                $response = curl_exec($curl);
                $err = curl_error($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                if ($err) {
                    $errorMsg = "cURL Error: " . $err;
                    if (class_exists('AdminClientifyController')) {
                        AdminClientifyController::addLog('error', "[$url] Intento $attempt - $errorMsg");
                    }
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay * $attempt);
                        continue;
                    }
                } else {
                    if ($httpCode >= 400) {
                        $errorMsg = "HTTP Error: " . $httpCode . " | Response: " . substr($response, 0, 150);
                        if (class_exists('AdminClientifyController')) {
                            AdminClientifyController::addLog('error', "[$url] HTTP $httpCode - $errorMsg");
                        }
                        return array('success' => false, 'error' => $errorMsg, 'http_code' => $httpCode);
                    }
                    return array('success' => true, 'response' => $response, 'http_code' => $httpCode);
                }
            }
            return array('success' => false, 'error' => $errorMsg);
        }

        private function request($end_point, $method, $data, $key)
        {
            $headers = array(
                'Content-Type:application/json',
                'Authorization:Token ' . $key
            );
            $result = $this->makeCurlRequest($this->api_url . $end_point, $method, $data, $headers);
            if (!$result['success']) {
                return (object) array('detail' => $result['error']);
            }
            $decoded = json_decode($result['response']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return (object) array('detail' => "JSON Decode Error: " . json_last_error_msg() . " | Raw response: " . substr($result['response'], 0, 150));
            }
            return $decoded;
        }

        public function Post_Base_Clientify($data, $key)
        {
            return $this->request('connection_by_plugin/', 'POST', $data, $key);
        }

        public function Post_Order_Clientify($data)
        {
            return $this->request('prestashop_listener', 'POST', $data, $this->api_key);
        }

        public function Post_Contacts_Clientify($data)
        {
            return $this->request('prestashop_listener', 'POST', $data, $this->api_key);
        }

        public function Get_Api($end_point)
        {
            return $this->request($end_point, 'GET', null, $this->api_key);
        }
    }
}
