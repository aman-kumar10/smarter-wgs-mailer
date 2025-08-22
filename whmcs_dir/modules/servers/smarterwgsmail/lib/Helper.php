<?php

namespace WHMCS\Module\Server\SmarterWgsMail;

use Exception;
use WHMCS\Database\Capsule;
use WHMCS\Module\Server\SmarterWgsMail\Curl;
class Helper {
    
    public $serverusername;
    public $serverpassword;
    public $authResponse = [];

    public $userId = '';
    public $params = [];
    public $serviceId = '';
    public $productId = '';

    function __construct($params = []) {

        $this->params = $params;

        $this->serverusername = $params['serverusername'];
        $this->serverpassword = $params['serverpassword'];

        $this->userId = $params['userid'];
        $this->serviceId = $params['serviceid'];
        $this->productId = $params['pid'];

    }


    /**
     * Login DA token
    */
    function apiLoginDAtoken($params)
    {
        $curl = new Curl($params);

        $endpoint = '/api/v1/settings/sysadmin/manage-domain/' . $params["domain"];
        $data = [
            'username' => $this->serverusername,
            'password' => $this->serverpassword
        ];

        $response = $curl->curlCall($endpoint, $data, "POST", "manage-domain");

        if($response['httpcode'] == 200 && $response['result']['success'] == true) {
            return $response['result']['impersonateAccessToken'];
        }

        return $response;
    }


    /** 
     * Product custom fields
    */
    public static function create_custom_fields($productId)
    {
        try {
            
            $customFields = [
                [
                    'type'        => 'product',
                    'relid'       => $productId,
                    'fieldname'   => 'sm_hostname | Hostname',
                    'fieldtype'   => 'text',
                    'description' => '',
                    'fieldoptions'=> '',
                    'regexpr'     => '',
                    'adminonly'   => '',
                    'required'    => '',
                    'showorder'   => 'on',
                    'showinvoice' => 'on',
                    'sortorder'   => 0,
                ],
                [
                    'type'        => 'product',
                    'relid'       => $productId,
                    'fieldname'   => 'sm_username | Domain Administrator Username',
                    'fieldtype'   => 'text',
                    'description' => '',
                    'fieldoptions'=> '',
                    'regexpr'     => '',
                    'adminonly'   => '',
                    'required'    => '',
                    'showorder'   => 'on',
                    'showinvoice' => 'on',
                    'sortorder'   => 0,
                ],
                [
                    'type'        => 'product',
                    'relid'       => $productId,
                    'fieldname'   => 'sm_password |Password',
                    'fieldtype'   => 'password',
                    'description' => '',
                    'fieldoptions'=> '',
                    'regexpr'     => '',
                    'adminonly'   => '',
                    'required'    => '',
                    'showorder'   => 'on',
                    'showinvoice' => 'on',
                    'sortorder'   => 0,
                ],
            ];

            foreach ($customFields as $field) {
                $exists = Capsule::table('tblcustomfields')
                    ->where('type', $field['type'])
                    ->where('relid', $field['relid'])
                    ->where('fieldname', $field['fieldname'])
                    ->exists();

                if (!$exists) {
                    Capsule::table('tblcustomfields')->insert($field);
                }
            }
        } catch(Exception $e) {
            logActivity("Error to create cusotom fields with product-{$productId}, Error: ".$e->getMessage());
        }
    }

    /**
     * Product config options 
    */
    public static function create_config_options($productId)
    {
        try {
            $groupName = 'Smarter WGS Mail';
            $groupId = Capsule::table('tblproductconfiggroups')
                ->where('name', $groupName)
                ->value('id');

            if (!$groupId) {
                $groupId = Capsule::table('tblproductconfiggroups')->insertGetId([
                    'name'       => $groupName,
                    'description'=> 'Smarter WGS Mail group',
                ]);
            }

            $linked = Capsule::table('tblproductconfiglinks')
                ->where('gid', $groupId)
                ->where('pid', $productId)
                ->exists();

            if (!$linked) {
                Capsule::table('tblproductconfiglinks')->insert([
                    'gid' => $groupId,
                    'pid' => $productId,
                ]);
            }

            $options = [
                ['domain_Qupgrade|Domain Quote Upgrade', 1, 10],
                ['user_Aliases|User Aliases', 0, 50],
                ['mail_Bsize|Mailbox Size', 0, 10],
                ['esa_Dquota|ESA Device Quota', 0, 10],
                ['mapiews_Dquota|MAPI/EWS Device Quota', 0, 10],
                ['domain_Aquota|Domain Aliases Quota', 0, 10],
            ];

            foreach ($options as $opt) {
                [$name, $min, $max] = $opt;

                $exists = Capsule::table('tblproductconfigoptions')
                    ->where('gid', $groupId)
                    ->where('optionname', $name)
                    ->exists();

                if (!$exists) {
                    Capsule::table('tblproductconfigoptions')->insert([
                        'gid'        => $groupId,
                        'optionname' => $name,
                        'optiontype' => 4,
                        'qtyminimum' => $min,
                        'qtymaximum' => $max,
                    ]);
                }
            }

        } catch (Exception $e) {
            logActivity("Error creating SmarterWgsMail Config Options: " . $e->getMessage());
        }
    }


    /**
     * Domain Put
    */
    public function sysadmin_domainPut($data) {
        try {

            $curl = new Curl($this->params);
            $endPoint = '/api/v1/settings/sysadmin/domain-put';
            $response = $curl->curlCall($endPoint, $data, 'POST', 'domain-put');

            return $response;

        } catch(Exception $e) {
            logActivity("Error in domain-put, Error: ".$e->getMessage());
        }
    }


    /**
     * Domain Settings
    */
    public function sysadmin_domainSettings($data) {
        try {

            $curl = new Curl($this->params);
            $endPoint = '/api/v1/settings/sysadmin/domain-settings/'.$this->params["domain"];

            $response = $curl->curlCall($endPoint, $data, 'POST', 'domain-settings');
            return $response;

        } catch(Exception $e) {
            logActivity("Error in domain-settings, Error: ".$e->getMessage());
        }
    }


    /**
     * Domain Delete
    */
    public function sysadmin_domainDelete($data, $terminate = '') {
        try {

            $delDomTF = '/true';
            if ($terminate === 'terminate') {
                $delDomTF = ($this->params['configoption21'] === 'on') ? '/true' : '/false';
            }

            $curl = new Curl($this->params);
            $endPoint = '/api/v1/settings/sysadmin/domain-delete/'.$this->params["domain"].$delDomTF;

            $response = $curl->curlCall($endPoint, $data, 'POST', 'domain-delete');
            return $response;

        } catch(Exception $e) {
            logActivity("Error in domain-delete, Error: ".$e->getMessage());
        }
    }


    /**
     * User Default
    */
    public function domain_userDefault($data) {
        try {

            $curl = new Curl($this->params);

            $tokenDA = $this->apiLoginDAtoken($this->params);
            $endPoint = '/api/v1/settings/domain/user-defaults';

            $response = $curl->curlCall($endPoint, $data, 'POST', 'user-defaults', $tokenDA);
            return $response;

        } catch(Exception $e) {
            logActivity("Error in user-defaults, Error: ".$e->getMessage());
        }
    }

    /**
     * Propagate Settings
    */
    public function domain_propagateSettings($data) {
        try {

            $curl = new Curl($this->params);

            $tokenDA = $this->apiLoginDAtoken($this->params);
            $endPoint = '/api/v1/settings/domain/propagate-settings';

            $response = $curl->curlCall($endPoint, $data, 'POST', 'propagate-settings', $tokenDA);
            return $response;

        } catch(Exception $e) {
            logActivity("Error in propagate-settings, Error: ".$e->getMessage());
        }
    }


    /**
     * Get Domain Data
    */
    public function sysadmin_getDomainData() {
        try {

            $curl = new Curl($this->params);
            $endPoint = '/api/v1/settings/sysadmin/domain/'.$this->params['domain'];

            $response = $curl->curlCall($endPoint, (object)[], 'GET', 'get-domain-data');
            return $response;

        } catch(Exception $e) {
            logActivity("Error in propagate-settings, Error: ".$e->getMessage());
        }
    }


    /**
     * Get Domain Info
    */
    public function sysadmin_getDomainInfo() {
        try {

            $license = $this->sysadmin_getDomainLicense();
            $settings = $this->sysadmin_getDomainSettings();

            if($license['httpcode'] == 200 && ($license['result']['success'] == 1 || $license['result']['success'] == true)) {
                $license_res =  $license['result']['domainSettings'];
            } else {
                $license_res = $license['result']['message'];
            }

            if($settings['httpcode'] == 200 && ($settings['result']['success'] == 1 || $settings['result']['success'] == true)) {
                $settings_res =  $settings['result']['domainSettings'];
            } else {
                $settings_res = $settings['result']['message'];
            }

            return [
                'license' => $license_res,
                'settings' => $settings_res,
            ];

        } catch(Exception $e) {
            logActivity("Error in propagate-settings, Error: ".$e->getMessage());
        }
    }

    // Get Domain License
    public function sysadmin_getDomainLicense() {
        try {

            $curl = new Curl($this->params);

            $endPoint = '/api/v1/licensing/about';
            $response = $curl->curlCall($endPoint, (object)[], 'GET', 'domain-license');

            return $response;

        } catch(Exception $e) {
            logActivity("Error in propagate-settings, Error: ".$e->getMessage());
        }
    }

    // Get Domain Settings
    public function sysadmin_getDomainSettings() {
        try {

            $curl = new Curl($this->params);

            $tokenDA = $this->apiLoginDAtoken($this->params);
            $endPoint = 'api/v1/settings/domain/domain';
            $response = $curl->curlCall($endPoint, (object)[], 'GET', 'domain-settings', $tokenDA);

            return $response;

        } catch(Exception $e) {
            logActivity("Error in propagate-settings, Error: ".$e->getMessage());
        }
    }
}