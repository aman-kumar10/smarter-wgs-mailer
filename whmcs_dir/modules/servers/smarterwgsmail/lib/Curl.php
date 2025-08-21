<?php

namespace WHMCS\Module\Server\SmarterWgsMail;

use WHMCS\Database\Capsule;


class Curl {
    private $serverhostname = '';
    private $serverusername = '';
    private $serverpassword = '';
    private $baseUrl = '';
    public $token = '';

    public $userId = '';
    public $serviceId = '';
    public $productId = '';

    public function __construct($params = []) {
        if (!empty($params)) {
            $this->serverhostname = $params['serverhostname'];
            $this->serverusername = $params['serverusername'];
            $this->serverpassword = $params['serverpassword'];

            $this->userId = $params['userid'];
            $this->serviceId = $params['serviceid'];
            $this->productId = $params['pid'];

            $this->baseUrl = "https://" . $this->serverhostname . "/api/v1/";
        }
    }

    public function setToken($token) {
        $this->token = $token;
    }

    public function curlCall($endPoint, $data = [], $method = 'GET', $action = '') {
        try {
            $url = $this->baseUrl . $endPoint;

            if (strtoupper($method) === 'GET' && !empty($data)) {
                $url .= '?' . http_build_query($data);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            $headers = [];
            if (!empty($this->token)) {
                $headers[] = 'Authorization: Bearer ' . $this->token;
            }

            if (strtoupper($method) !== 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if (!empty($data)) {
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new \Exception("cURL Error: " . $error);
            }

            curl_close($ch);

            logModuleCall('SmarterWgsMail', $action, $url, json_encode($data), $response, []);

            return [
                'httpcode' => $httpCode,
                'result'   => json_decode($response, true)
            ];

        } catch (Exception $e) {
            return [
                'httpcode' => 500,
                'error'    => $e->getMessage()
            ];
        }
    }
}
