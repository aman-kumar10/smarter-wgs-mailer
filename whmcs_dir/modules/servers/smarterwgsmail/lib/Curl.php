<?php

namespace WHMCS\Module\Server\SmarterWgsMail;

use Exception;
use WHMCS\Database\Capsule;


class Curl {
    public $serverhostname = '';
    public $serverusername = '';
    public $serverpassword = '';
    public $baseUrl = '';
    public $token = '';
    public $authResponse = '';

    public $userId = '';
    public $serviceId = '';
    public $productId = '';

    public function __construct($params = []) {
        try {
            if (!empty($params)) {
                $this->serverhostname = $params['serverhostname'];
                $this->serverusername = $params['serverusername'];
                $this->serverpassword = $params['serverpassword'];

                $this->userId = $params['userid'];
                $this->serviceId = $params['serviceid'];
                $this->productId = $params['pid'];

                $this->baseUrl = "https://" . $this->serverhostname;

                if (!empty($this->serverpassword)) {
                    $data = [
                        'username' => $this->serverusername,
                        'password' => $this->serverpassword
                    ];
    
                    $this->authResponse = $this->curlCall("/api/v1/auth/authenticate-user", $data, 'POST', 'GetToken');
    
                    if ($this->authResponse['httpcode'] == 200 && $this->authResponse['result']['success'] == 1) {
                        $this->token = $this->authResponse['result']['accessToken'];
                    }
                }

            }

        } catch(Exception $e) {
            logActivity("Error in Curl Construtor. Error: ".$e->getMessage());
        }


    }

    public function setToken($token) {
        $this->token = $token;
    }

    public function curlCall($endPoint, $data = [], $method = 'GET', $action = '', $tokenDA = '') {
        try {

            if(!empty($tokenDA)) {
                $this->token = $tokenDA;
            }

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

            // Token handling
            $headers = [];
            $activeToken = !empty($tokenDA) ? $tokenDA : $this->token;
            if (!empty($activeToken)) {
                $headers[] = 'Authorization: Bearer ' . $activeToken;
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
