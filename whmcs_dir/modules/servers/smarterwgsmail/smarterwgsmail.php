<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Server\SmarterWgsMail\Helper;



if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}




function smarterwgsmail_MetaData()
{
    return array(
        'DisplayName' => 'Smarter WGS Mail',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '1111',
        'DefaultSSLPort' => '1112',
        'ServiceSingleSignOnLabel' => 'Login to Panel as User',
        'AdminSingleSignOnLabel' => 'Login to Panel as Admin',
    );
}



function smarterwgsmail_ConfigOptions($params)
{
    global $whmcs;

    $helper = new Helper($params);
    $pid = $whmcs->get_req_var('id') ?: null;

    $helper = new Helper;
    $helper->create_custom_fields($whmcs->get_req_var('id'));
    $helper->create_config_options($whmcs->get_req_var('id'));

    return array(
        'Domain Folder Path' => array(
            'Type' => 'text',
            'Size' => '25',
            'Description' => 'Enter in megabytes',
        ),
        'Outbound IP Address' => array(
            'Type' => 'text',
            'Size' => '25',
            'Description' => 'Enter in megabytes',
        ),
        'Users' => array(
            'Type' => 'text',
            'Size' => '25',
            'Description' => '0 = unlimited',
        ),
        'Mailbox Size Limit (MB)' => array(
            'Type' => 'text',
            'Size' => '25',
            'Description' => '0 = unlimited',
        ),
        'User Aliases' => array(
            'Type' => 'text',
            'Size' => '25',
            'Description' => '0 = unlimited, -1 = disable',
        ),
        'Domain Aliases' => array(
            'Type' => 'text',
            'Size' => '25',
            'Description' => '0 = unlimited, -1 = disable',
        ),
        'EAS Accounts' => array(
            'Type' => 'text',
            'Size' => '25',
            'Description' => '0 = unlimited, -1 = disable (Enterprise Only)',
        ),
        'Mailing Lists' => array(
            'Type' => 'text',
            'Size' => '25',
            'Description' => '0 = unlimited, -1 = disable',
        ),
        'MAPI/EWS Accounts' => array(
            'Type' => 'text',
            'Size' => '25',
            'Description' => '0 = unlimited, -1 = disable (Enterprise Only)',
        ),
        'Domain Disk Space Limit (MB)' => array(
            'Type' => 'text',
            'Size' => '25',
            'Description' => '0 = unlimited',
        ),
        
        'Active Directory Integration' => array(
            'Type' => 'yesno',
            'Description' => '(Enterprise Only)',
        ),
        'Webmail Login Customization' => array(
            'Type' => 'yesno',
            'Description' => '',
        ),
        'Automated Forwarding' => array(
            'Type' => 'yesno',
            'Description' => '',
        ),
        'SMTP Accounts' => array(
            'Type' => 'yesno',
            'Description' => '',
        ),
        'Chat (XMPP)' => array(
            'Type' => 'yesno',
            'Description' => '(Enterprise Only)',
        ),
        'Disposable Address' => array(
            'Type' => 'yesno',
            'Description' => '',
        ),
        'File Storage' => array(
            'Type' => 'yesno',
            'Description' => '',
        ),
        'Global Address List' => array(
            'Type' => 'yesno',
            'Description' => '',
        ),
        'Online Meetings' => array(
            'Type' => 'yesno',
            'Description' => '(Enterprise Only)',
        ),
        'Two-Step Authentication' => array(
            'Type' => 'yesno',
            'Description' => '',
        ),
        'Remove domain data on delete' => array(
            'Type' => 'yesno',
            'Description' => '',
        ),
        'Allow domain administrators to manage Mailbox Size Limit' => array(
            'Type' => 'yesno',
            'Description' => '',
        ),
    );
}


function smarterwgsmail_TestConnection( $params)
{
    try {

        $errorMsg  = '';
        $success = '';

        $helper = new Helper($params);
        $curlRes = $helper->smarterWgsMailertestConn();

        if ($curlRes['httpcode'] == 200 && $curlRes['result']['success'] == 1) {
            $token = $curlRes['result']['accessToken'];

            Capsule::table("tblservers")->where("name", "Smarter Wgs Mailer")->where("hostname", $params['serverhostname'])->where("type", "smarterwgsmail")->update([
                'accesshash' => $token,
            ]);

            $success = true;
            
        } else {
            $errorMsg = $curlRes['result']->getMessage;
        }

        return array('success' => $success, 'error' => $errorMsg);

    } catch (Exception $e) {
        logModuleCall(
            'smarterwgsmail',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $success = false;
        $errorMsg = $e->getMessage();
    }

    return array(
        'success' => $success,
        'error' => $errorMsg,
    );
}

function smarterwgsmail_CreateAccount( $params)
{
    try {
        // 
        $helper = new Helper($params);


        $maxUsers = intval($params["configoption3"]);
        $maxMailboxSize = intval($params["configoption4"]);
        $maxDomainSize = intval($params["configoption10"]);
        $maxAliases = intval($params["configoption5"]);
        $maxDomainAliases = intval($params["configoption6"]);
        $maxMailingLists = intval($params["configoption8"]);
        $allocatedExchange = 0;
        $allocatedEas = intval($params["configoption7"]);
        $allocatedEasManagement = false;
        $allocatedMapi = intval($params["configoption9"]);
        $allocatedMapiManagement = false;
        $domainAliasManagement = false;
        $maxMailboxSizeManagement = false;
        $mailingListManagement = false;

        $hostname = 'mail.'.$params["domain"];
        $adminUsername = $params["username"];
        $adminPassword = $params["password"];

        if(isset($params["configoption3"]) && $params["configoption3"] == "default"){
            $params["configoption3"] = "";
        }

        // Config options
        if(isset($params["configoptions"])){
            if(isset($params["configoptions"]["users"]) && is_numeric($params["configoptions"]["users"])){
                if($maxUsers == -1)
                    $maxUsers = 0;
                $conMaxUsers = intval($params["configoptions"]["users"]);
                if($conMaxUsers < 0)
                    $maxUsers = abs($conMaxUsers);
                else
                    $maxUsers += $conMaxUsers;
            }

            if(isset($params["configoptions"]["mailbox_size"]) && is_numeric($params["configoptions"]["mailbox_size"])){
                if($maxMailboxSize == -1)
                    $maxMailboxSize = 0;
                $conMaxMailboxSize = intval($params["configoptions"]["mailbox_size"]);
                if($conMaxMailboxSize < 0)
                    $maxMailboxSize = abs($conMaxMailboxSize);
                else
                    $maxMailboxSize += $conMaxMailboxSize;
            }

            if(isset($params["configoptions"]["domain_size"]) && is_numeric($params["configoptions"]["domain_size"])){
                if($maxDomainSize == -1)
                    $maxDomainSize = 0;
                $conDomainSize = intval($params["configoptions"]["domain_size"]);
                if($conDomainSize < 0)
                    $maxDomainSize = abs($conDomainSize);
                else 
                    $maxDomainSize += $conDomainSize;
            }

            if(isset($params["configoptions"]["aliases"]) && is_numeric($params["configoptions"]["aliases"])){
                if($maxAliases == -1)
                    $maxAliases = 0;
                $conMaxAliases = intval($params["configoptions"]["aliases"]);
                if($conMaxAliases < 0)
                    $maxAliases = abs($conMaxAliases);
                else
                    $maxAliases += $conMaxAliases;
            }

            if(isset($params["configoptions"]["domain_aliases"]) && is_numeric($params["configoptions"]["domain_aliases"])){
                if($maxDomainAliases == -1)
                    $maxDomainAliases = 0;
                $conMaxDomainAliases = intval($params["configoptions"]["domain_aliases"]);
                if($conMaxDomainAliases < 0)
                    $maxDomainAliases = abs($conMaxDomainAliases);
                else
                    $maxDomainAliases += $conMaxDomainAliases;
            }

            if(isset($params["configoptions"]["accounts_eas"]) && is_numeric($params["configoptions"]["accounts_eas"])){
                if($allocatedEas == -1)
                    $allocatedEas = 0;
                $conMaxAllocatedEas = intval($params["configoptions"]["accounts_eas"]);
                if($conMaxAllocatedEas < 0)
                    $allocatedEas = abs($conMaxAllocatedEas);
                else
                    $allocatedEas += $conMaxAllocatedEas;
            }

            if(isset($params["configoptions"]["accounts_mapiews"]) && is_numeric($params["configoptions"]["accounts_mapiews"])){
                if($allocatedMapi == -1)
                    $allocatedMapi = 0;
                $conMaxAllocatedMapi = intval($params["configoptions"]["accounts_mapiews"]);
                if($conMaxAllocatedMapi < 0)
                    $allocatedMapi = abs($conMaxAllocatedMapi);
                else
                    $allocatedMapi += $conMaxAllocatedMapi;
            }

                if(isset($params["configoptions"]["accounts_exchange"]) && is_numeric($params["configoptions"]["accounts_exchange"])){
                    $exch = intval($params["configoptions"]["accounts_exchange"]);
                    if($exch < 0)
                        $allocatedExchange = abs($exch);
                    else 
                        $allocatedExchange += $exch;
                }

        }

        // Custom fields
        if(isset($params["customfields"])){
            if(isset($params["customfields"]["sm_hostname"]) && strlen($params["customfields"]["sm_hostname"]) > 0){
                $hostname = $params["customfields"]["sm_hostname"];
            }
            if(isset($params["customfields"]["sm_username"]) && strlen($params["customfields"]["sm_username"]) > 0){
                $adminUsername = $params["customfields"]["sm_username"];
            }
            if(isset($params["customfields"]["sm_password"]) && strlen($params["customfields"]["sm_password"]) > 0){
                $adminPassword = $params["customfields"]["sm_password"];
            }
        }

        //Validation

        if($maxUsers < 1)
            return "Error: User count must be greater than 0.";
        if(strlen($hostname) < 1)
            return "Error: Blank hostname.";
        if(strlen($adminUsername) < 1)
            return "Error: Blank admin username.";
        if(strlen($adminPassword) < 1)
            return "Error: Blank admin password.";
        //Check for reserved name usageS
        $reservedUsernames = array("CON", "PRN", "AUX", "CLOCK$", "NUL", "COM0", "COM1", "COM2", "COM3", "COM4","COM5", "COM6", "COM7", "COM8", "COM9", "LPT0", "LPT1", "LPT2", "LPT3", "LPT4","LPT5", "LPT6", "LPT7", "LPT8", "LPT9");
        for($i = 0; $i < count($reservedUsernames);$i++){
            if(strcasecmp($reservedUsernames[$i], $adminUsername) == 0)
                return "Error: ".$adminUsername." is a reserved username.";
        }
        //REGEX match hostname against hostname validation
        $validateHostname = preg_match('/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/', $hostname, $matches, PREG_OFFSET_CAPTURE);
        if($validateHostname == 0 || $validateHostname == false)
            return "Error: Invalid hostname. (".$hostname.")";


        if($allocatedExchange > 0)
        {
            if($allocatedEas == -1)
                $allocatedEas = 0;
            if($allocatedMapi == -1)
                $allocatedMapi = 0;
        }

        $allocatedEas = $allocatedEas + $allocatedExchange;
        $allocatedMapi = $allocatedMapi + $allocatedExchange;

        if($allocatedEas > -1)
            $allocatedEasManagement = true;
        if($allocatedMapi > -1)
            $allocatedMapiManagement = true;
        if($maxDomainAliases > -1)
            $domainAliasManagement = true;
        if ($params["configoption22"] == "on") 
            $maxMailboxSizeManagement = true;
        if($maxMailingLists > -1)
            $mailingListManagement = true;

        //Normalize values, this is after logic!
        $allocatedEas = max(0, $allocatedEas);
        $allocatedMapi = max(0, $allocatedMapi);
        $maxDomainAliases = max(0, $maxDomainAliases);
        $maxMailboxSize = max(0, $maxMailboxSize); //
        $maxDomainSize = max(0, $maxDomainSize); //
        $maxAliases = max(0, $maxAliases);
        $maxMailingLists = max(0, $maxMailingLists);


        //Post data
        $inputData = [
            'adminUsername'=> $adminUsername,
            'adminPassword'=> $adminPassword,
            'deliverLocallyForExternalDomain' => false,
            'domainLocation' => 0,
            'domainLocationAddress' => '',
            'domainData' => [
                'name' => $params["domain"],
                'hostname' => $hostname,
                'path'=> $params["configoption1"].$params["domain"],
                'mainDomainAdmin'=> $adminUsername,
                'outgoingIP'=> $params["configoption2"],
                'aliasLimit' => $maxAliases,
                'maxSize' => $maxDomainSize*1024 * 1024,
                'userLimit' => $maxUsers,
                'listLimit'  => $maxMailingLists
            ]
        ];


        $curlRes = $helper->registerDomain($inputData);

        if($curlRes['httpcode'] == 200 && $curlRes['result']['success'] == 1) {
            return 'success';
        } else {
            return $curlRes['result']['message'];
        }
        
    } catch(Exception $e) {
        logActivity("Error to Create Account for SmarterWgsMail. Error: ".$e->getMessage());
    }
}