<?php
if (!class_exists('ClientifyApi')) {
    //include_once(__DIR__ . '/AdminClientify.php');

    class ClientifyApi
    {
        var $api_key;

        // var $api_url = 'https://ecommerce-aly.ngrok.io/api/ecommerce/v2/';
        var $api_url = 'https://api-plus.clientify.com/api/ecommerce/v2/';

        public function __construct()
        {
            $results_global = Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "configuration_clientify");
            $this->api_key = $results_global[0]['clientify_api_key'];
        }

        private function makeCurlRequest($url, $method = 'GET', $data = null, $headers = [])
        {
            $maxRetries = 3;
            $retryDelay = 1;
            // $logPath = __DIR__ . '/../logs/';
            // if (!is_dir($logPath)) {
            //     mkdir($logPath, 0755, true);
            // }
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => $method,
                ));
                if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
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
                    $log = date('Y-m-d H:i:s') . " - Attempt $attempt - $errorMsg\n";
                    file_put_contents($logPath . 'api_errors.log', $log, FILE_APPEND);
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay * $attempt);
                        continue;
                    }
                } else {
                    if ($httpCode >= 400) {
                        $errorMsg = "HTTP Error: " . $httpCode . " | Response: " . substr($response, 0, 150);
                        $log = date('Y-m-d H:i:s') . " - HTTP $httpCode - $errorMsg\n";
                        file_put_contents($logPath . 'api_errors.log', $log, FILE_APPEND);
                        return ['success' => false, 'error' => $errorMsg, 'http_code' => $httpCode];
                    }
                    return ['success' => true, 'response' => $response, 'http_code' => $httpCode];
                }
            }
            return ['success' => false, 'error' => $errorMsg];
        }

        public function Post_Base_Clientify($data, $key)
        {
            $headers = array(
                'Content-Type:application/json',
                'Authorization:Token ' . $key            
            );
            $result = $this->makeCurlRequest($this->api_url . 'connection_by_plugin/', 'POST', $data, $headers);
            if (!$result['success']) {
                return (object) ['detail' => $result['error']];
            }
            $decoded = json_decode($result['response']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return (object) ['detail' => "JSON Decode Error: " . json_last_error_msg() . " | Raw response: " . substr($result['response'], 0, 150)];
            }
            return $decoded;
        }

        public function Post_Order_Clientify($data)
        {
            $headers = array(
                'Content-Type:application/json',
                'Authorization:Token ' . $this->api_key
            );
            $result = $this->makeCurlRequest($this->api_url . 'prestashop_listener', 'POST', $data, $headers);
            if (!$result['success']) {
                return (object) ['detail' => $result['error']];
            }
            $decoded = json_decode($result['response']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return (object) ['detail' => "JSON Decode Error: " . json_last_error_msg() . " | Raw response: " . substr($result['response'], 0, 150)];
            }
            return $decoded;
        }

        public function Post_Contacts_Clientify($data)
        {
            $headers = array(
                'Content-Type:application/json',
                'Authorization:Token ' . $this->api_key
            );
            $result = $this->makeCurlRequest($this->api_url . 'prestashop_listener', 'POST', $data, $headers);
            if (!$result['success']) {
                return (object) ['detail' => $result['error']];
            }
            $decoded = json_decode($result['response']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return (object) ['detail' => "JSON Decode Error: " . json_last_error_msg() . " | Raw response: " . substr($result['response'], 0, 150)];
            }
            return $decoded;
        }

        public function Get_Api($end_point)
        {
            $headers = array(
                'Content-Type:application/json',
                'Authorization:Token ' . $this->api_key
            );
            $result = $this->makeCurlRequest($this->api_url . $end_point, 'GET', null, $headers);
            if (!$result['success']) {
                return (object) ['detail' => $result['error']];
            }
            $decoded = json_decode($result['response']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return (object) ['detail' => "JSON Decode Error: " . json_last_error_msg() . " | Raw response: " . substr($result['response'], 0, 150)];
            }
            return $decoded;
        }

    }
}
