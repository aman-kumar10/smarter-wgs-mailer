<?php

namespace WHMCS\Module\Server\SmarterWgsMail;

use Exception;
use WHMCS\Database\Capsule;
use WHMCS\Module\Server\SmarterWgsMail\Curl;
class Helper {
    
    public $curl;
    public $token = '';
    public $authResponse = [];

    function __construct($params = []) {
        $this->curl = new Curl($params);

        if (!empty($params['serverpassword'])) {
            $data = [
                'username' => $params['serverusername'],
                'password' => $params['serverpassword']
            ];

            $this->authResponse = $this->curl->curlCall("auth/authenticate-user", $data, 'POST', 'GetToken');

            if ($this->authResponse['httpcode'] == 200 && $this->authResponse['result']['success'] == 1) {
                $this->token = $this->authResponse['result']['accessToken'];
                $this->curl->setToken($this->token);
            }
        }
    }

    /**
     * Test connection 
    */ 
    public function smarterWgsMailertestConn() {
        try {

            if (empty($this->authResponse)) {
                return [
                    'httpcode' => 500,
                    'error'    => 'No authentication attempt was made.'
                ];
            }
            return $this->authResponse;

        } catch(Exception $e) {
            logActivity("Error in Smarter Wgs Mailer Server Test Connection. Error: ".$e->getMessage());
        }
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
     * Register domain
    */
    public function registerDomain($data) {
        try {
            $endPoint = 'settings/sysadmin/domain-put';
            $response = $this->curl->curlCall($endPoint, $data, 'POST', 'registerDomain');
            // echo "<pre>"; print_r($response); die;
            return $response;
        } catch(Exception $e) {
            logActivity("Error to register domain in SmarterWgsMail. Error: ".$e->getMessage());
        }
    }

}