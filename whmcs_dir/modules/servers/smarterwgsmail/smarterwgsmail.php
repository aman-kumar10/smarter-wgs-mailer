<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Server\SmarterWgsMail\Curl;
use WHMCS\Module\Server\SmarterWgsMail\Helper;



if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


/**
 * Meta Data
*/
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


/**
 * Config Options
*/
function smarterwgsmail_ConfigOptions($params)
{
    global $whmcs;

    $helper = new Helper;
    $helper->create_custom_fields($whmcs->get_req_var('id'));
    $helper->create_config_options($whmcs->get_req_var('id'));

    return [
        "configoption1" => [
            "FriendlyName" => "Domain Folder Path",
            "Type" => "text", 
            "Size" => "15", 
            "Description" => "",
            "Default" => "C:\\SmarterMail\\Domains\\",
        ],
        "configoption2" => [
            "FriendlyName" => "Outbound IP Address",
            "Type" => "text", 
            "Size" => "14", 
            "Description" => "",
            "Default" => "default",
        ],
        "configoption3" => [
            "FriendlyName" => "Users",
            "Type" => "text",
            "Description" => "0 = unlimited",
            "Size" => "14",
			"Default" => "5",
        ],
        "configoption4" => [
            "FriendlyName" => "Mailbox Size Limit (MB)",
            "Type" => "text", 
            "Size" => "14",
            "Description" => "0 = unlimited",
            "Default" => "1000",
        ],
        "configoption5" => [
            "FriendlyName" => "User Aliases",
            "Type" => "text", 
            "Size" => "14",
            "Description" => "0 = unlimited, -1 = disable",
			"Default" => "-1",
        ],
        "configoption6" => [
            "FriendlyName" => "Domain Aliases",
            "Type" => "text", 
            "Size" => "5",
            "Description" => "0 = unlimited, -1 = disable",
			"Default" => "-1",
        ],
        "configoption7" => [
            "FriendlyName" => "EAS Accounts",
            "Type" => "text",
            "Size" => "14",
            "Description" => "0 = unlimited, -1 = disable, (Enterprise Only)",
			"Default" => "-1",
        ],
        "configoption8" => [
            "FriendlyName" => "Mailing Lists",
            "Type" => "text",
            "Size" => "14",
            "Description" => "0 = unlimited, -1 = disable",
			"Default" => "0",
        ],
        "configoption9" => [
            "FriendlyName" => "MAPI/EWS Accounts",
            "Type" => "text",
            "Size" => "14",
            "Description" => "0 = unlimited, -1 = disable, (Enterprise Only)",
			"Default" => "-1",
        ],
        "configoption10" => [
            "FriendlyName" => "Domain Disk Space Limit (MB)",
            "Type" => "text",
            "Size" => "14",
            "Description" => "0 = unlimited",
			"Default" => "0",
        ],
        "configoption11" => [
            "FriendlyName" => "Active Directory Integration",
            "Type" => "yesno",
            "Size" => "14",
			"Description" => "(Enterprise Only)",
            "Default" => "",
        ],
        "configoption12" => [
            "FriendlyName" => "Webmail Login Customization",
            "Type" => "yesno",
            "Size" => "14",
            "Default" => "true",
        ],
        "configoption13" => [
            "FriendlyName" => "Automated Forwarding",
            "Type" => "yesno",
            "Size" => "14",
            "Default" => "true",
        ],
        "configoption14" => [
            "FriendlyName" => "SMTP Accounts",
            "Type" => "yesno",
            "Size" => "14",
            "Default" => "true",
        ],
        "configoption15" => [
            "FriendlyName" => "Chat (XMPP)",
            "Type" => "yesno",
            "Size" => "14",
            "Default" => "true",
			"Description" => "(Enterprise Only)",
        ],
        "configoption16" => [
            "FriendlyName" => "Disposable Address",
            "Type" => "yesno",
            "Size" => "14",
            "Default" => "true",
        ],
        "configoption17" => [
            "FriendlyName" => "File Storage",
            "Type" => "yesno",
            "Size" => "14",
            "Default" => "true",
        ],
        "configoption18" => [
            "FriendlyName" => "Global Address List",
            "Type" => "yesno",
            "Size" => "16",
            "Description" => "",
            "Default" => "true",
        ],
        "configoption19" => [
            "FriendlyName" => "Online Meetings",
            "Type" => "yesno",
            "Size" => "14",
            "Description" => "(Enterprise Only)",
            "Default" => "true",
        ],
        "configoption20" => [
            "FriendlyName" => "Two-Step Authentication",
            "Type" => "yesno",
            "Size" => "14",
            "Description" => "",
            "Default" => "true",
        ],
        "configoption21" => [
            "FriendlyName" => "Remove domain data on delete",
            "Type" => "yesno",
            "Size" => "14",
            "Default" => "true",
        ], 
        "configoption22" => [
			"FriendlyName" => "Allow domain administrators to manage Mailbox Size Limit",
			"Type" => "yesno",
			"Size" => "14",
			"Default" => "false",
		],
    ];
}


/**
 * Test Connection
*/
function smarterwgsmail_TestConnection($params)
{
    try {

        $errorMsg  = '';
        $success = '';

        $curlRes = new Curl($params);

        $curlRes = (array)$curlRes;
        $authResponse = $curlRes['authResponse'];

        if ($authResponse['httpcode'] == 200 && ($authResponse['result']['success'] === 1 || $authResponse['result']['success'] === true)) {
            $token = $authResponse['result']['accessToken'];

            Capsule::table("tblservers")->where("name", "Smarter Wgs Mailer")->where("hostname", $params['serverhostname'])->where("type", "smarterwgsmail")->update([
                'accesshash' => $token,
            ]);

            $success = true;
            
        } else {
            $errorMsg = $authResponse['result']->getMessage;
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

/**
 * Create Account
*/
function smarterwgsmail_CreateAccount($params){
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
        $maxMailboxSize = max(0, $maxMailboxSize);
        $maxDomainSize = max(0, $maxDomainSize);
        $maxAliases = max(0, $maxAliases);
        $maxMailingLists = max(0, $maxMailingLists);


        //Post data
        $inputData1 = [
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


        // Post1
        $domainPut_res = $helper->sysadmin_domainPut($inputData1);

        if($domainPut_res['httpcode'] != 200 && ($domainPut_res['result']['success'] != 1 || $domainPut_res['result']['success'] != true)) {
            if(isset($domainPut_res['result']['message']) && $domainPut_res['result']['message'] != "") {
                $errMsg = $domainPut_res['result']['message'];
                if(str_starts_with($errMsg, "DOMAIN_ADD_ERROR_NAME_IN_USE")) {
                    return "Domain name is already in use.";
                }
                return $domainPut_res['result']['message'];
            }
            else {
                return "An error has occurred.";
            }
        }


        //Domain settings
        $inputData2 = array(
            'domainSettings' => array(
                'activeDirectoryIntegration' => (($params["configoption11"] == "on") ? true : false),
                'customLoginDisplay' => (($params["configoption12"] == "on") ? true : false),
                'enableMailForwarding'=> (($params["configoption13"] == "on") ? true : false),
                'enableSmtpAccounts'=> (($params["configoption14"] == "on") ? true : false),
                'enableXmpp'=> (($params["configoption15"] == "on") ? true : false),
                'enableDisposableAddresses' => (($params["configoption16"] == "on") ? true : false),
                'enableFileStorage' => (($params["configoption17"] == "on") ? true : false),
                'sharedGlobalAddressList' => (($params["configoption18"] == "on") ? true : false),
                'webConferencing' => (($params["configoption19"] == "on") ? true : false),
                'twoFactorSettings' => array(
                    'setting' => (($params["configoption20"] == "on") ? "1" : "0"),
                ),
                'maxActiveSyncAccounts' => $allocatedEas,
                'maxMapiEwsAccounts' => $allocatedMapi,
                'maxDomainAliases' => $maxDomainAliases,
                'enableActiveSyncAccountManagement' => ($allocatedEasManagement ? true : false),
                'enableMapiEwsAccountManagement' => ($allocatedMapiManagement ? true : false),
                'allowUserSizeChanging' => ($maxMailboxSizeManagement ? true : false),
                'showDomainAliasMenu' => ($domainAliasManagement ? true : false),
                'showListMenu' => ($mailingListManagement ? true : false)
            )
        );

        // Post 2
        $domainSettings_res = $helper->sysadmin_domainSettings($inputData2);
        if($domainSettings_res['httpcode'] != 200 && ($domainSettings_res['result']['success'] != 1 || $domainSettings_res['result']['success'] != true)) {
            
            logActivity("Removing domain, failed to apply settings correctly", 0);

            // Post3
            $domainDelete_res = $helper->sysadmin_domainDelete($inputData2);
            if(isset($domainSettings_res['result']['message']) && $domainSettings_res['result']['message'] != "") {
                return $domainSettings_res['result']['message'];
            } else {
                return "An error has occurred.";
            }
        }


        //User defaults
        $inputData3 = [
            'maxMailboxSize' => $maxMailboxSize*1024 * 1024,
            'services' => new stdClass()
        ];
        
        // Post4
        $userDefaults_res = $helper->domain_userDefault($inputData3);

        if($userDefaults_res['code'] != 200 && ($userDefaults_res['result']['success'] != 1 || $userDefaults_res['result']['success'] != true)) {

        } else {

            // Propagate Settings
            $inputData4 = [
                'globalUpdate' => [
                    ['userField' => 'MailboxSize', 'longValue' => $maxMailboxSize*1024 * 1024],
                ],
                'emails' => ["*@".$params["domain"]]
            ];

            // Post5
            $propagateSettings_res = $helper->domain_propagateSettings($inputData4);
        }


        return 'success';

        
    } catch(Exception $e) {
        logActivity("Error to Create Account for SmarterWgsMail, service #".$params['serviceid']." Error: ".$e->getMessage());
    }
}


/** ------------------------------------------------------------------------------------------ */


/**
 * Terminate Account
*/
function smarterwgsmail_TerminateAccount($params) {

    try {
        $helper = new Helper($params);

        // 
        $domainDelete_res = $helper->sysadmin_domainDelete((object)[], 'terminate');
        if($domainDelete_res['httpcode'] != 200 && ($domainDelete_res['result']['success'] != 1 || $domainDelete_res['result']['success'] != true)) {

            if(isset($domainDelete_res['result']['message']) && $domainDelete_res['result']['message'] != "") {
                return $domainDelete_res['result']['message'];
            } else {
                return "An error has occurred.";
            }
        }

        return "success"; 

    } catch(Exception $e) {
        logActivity("Error to Suspend Account, service #".$params['serviceid']." Error: ".$e->getMessage());
    }
}

/**
 * Suspend Account
*/
function smarterwgsmail_SuspendAccount($params) {
    try {
        $helper = new Helper($params);

        $inputData = [
            'domainSettings' => [
                'isEnabled' => false
            ] 
        ];

        // 
        $domainSettings_res = $helper->sysadmin_domainSettings($inputData);
        if($domainSettings_res['httpcode'] != 200 && ($domainSettings_res['result']['success'] != 1 || $domainSettings_res['result']['success'] != true)) {

            if(isset($domainSettings_res['result']['message']) && $domainSettings_res['result']['message'] != "") {
                return $domainSettings_res['result']['message'];
            } else {
                return "An error has occurred.";
            }
        }

        return "success"; 

    } catch(Exception $e) {
        logActivity("Error to Suspend Account, service #".$params['serviceid']." Error: ".$e->getMessage());
    }
}

/**
 * Unsuspend Account
*/
function smarterwgsmail_UnsuspendAccount($params) {
    try {
        $helper = new Helper($params);

        $inputData = [
            'domainSettings' => [
                'isEnabled' => true
            ] 
        ];

        // 
        $domainSettings_res = $helper->sysadmin_domainSettings($inputData);
        if($domainSettings_res['httpcode'] != 200 && ($domainSettings_res['result']['success'] != 1 || $domainSettings_res['result']['success'] != true)) {

            if(isset($domainSettings_res['result']['message']) && $domainSettings_res['result']['message'] != "") {
                return $domainSettings_res['result']['message'];
            } else {
                return "An error has occurred.";
            }
        }

        return "success"; 

    } catch(Exception $e) {
        logActivity("Error to Unsuspend Account, , service #".$params['serviceid'].". Error: ".$e->getMessage());
    }
}


/**
 * ClientArea Output
*/
function smarterwgsmail_ClientArea(array $params) {
    try {

        global $CONFIG;
        $helper = new Helper($params);

        $getDomainSettings = $helper->sysadmin_getDomainSettings();

        // Webmail Login URL
        $loginUrl = $helper->getWebmailLoginURL(!empty($getDomainSettings['mainDomainAdmin']) ? $getDomainSettings['mainDomainAdmin'] : '');
        if(!empty($loginUrl['url'])) {
            $webmailLoginUrl = $loginUrl['url'];
        } 

        $logInToWebmail = [
            'tabname' => "Log In To Webmail",
            'attID' => 'logInToWebmail',
            'href' => $webmailLoginUrl ?? '#',
            'attFaCls' => 'fa-sign-in',
        ];
        
        // get other management tabs 
        $managements = [];
        if(is_array($getDomainSettings) && !empty($getDomainSettings)) {
            if(!empty($getDomainSettings['enableMapiEwsAccountManagement']) && $getDomainSettings['enableMapiEwsAccountManagement']) {
                $managements["manageEwsLicenses"] = [
                    'tabname' => "Manage EWS Licenses",
                    'attID' => 'manageEwsLicenses',
                    'attFaCls' => 'fa-tasks',
                ];
            }
            if($getDomainSettings['enableActiveSyncAccountManagement']) {
                $managements["manageEasLicenses"] = [
                    'tabname' => "Manage EAS Licenses",
                    'attID' => 'manageEasLicenses',
                    'attFaCls' => 'fa-sitemap',
                ];
            }
            if($getDomainSettings['showListMenu']) {
                $managements["manageMailingLists"] = [
                    'tabname' => "Manage Mailing Lists",
                    'attID' => 'manageMailingLists',
                    'attFaCls' => 'fa-envelope',
                ];
            }
            if($getDomainSettings['showDomainAliasMenu']) {
                $managements["manageDomainAliases"] = [
                    'tabname' => "Manage Domain Aliases",
                    'attID' => 'manageDomainAliases',
                    'attFaCls' => 'fa-globe',
                ];
            }
        } 

        // Normal Client Area Page Load
        $defaultManagements = [
            'userAccount' => [
                'tabname' => "User Account",
                'attID' => 'userAccount',
                'attFaCls' => 'fa-user',
            ],
            'userAliases' => [
                'tabname' => "User Aliases",
                'attID' => 'userAliases',
                'attFaCls' => 'fa-random',
            ],
            'addUser' => [
                'tabname' => "Add User",
                'attID' => 'addUser',
                'attFaCls' => 'fa-user-plus',
            ],
            'addAlias' => [
                'tabname' => "Add Alias",
                'attID' => 'addAlias',
                'attFaCls' => 'fa-plus',
            ],
        ];


        $managements = array_merge($defaultManagements, $managements);

        $vars = [
            'assets_link' => $CONFIG["SystemURL"] . "/modules/servers/" . $params['model']->product->servertype . "/assets/",
            'managements' => $managements,
            'serviceId' => $params['serviceid'],
            'logInToWebmail' => $logInToWebmail,
        ];

        if($_GET['test'] == 'aman') {
            echo "<pre>"; print_r($vars); die;
        }


        return [
            'templatefile' => 'templates/overview.tpl',
            'vars' => $vars,
        ];

    } catch (Exception $e) {
        logModuleCall(
            $params['model']->product->servertype,
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return [
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => [
                'usefulErrorHelper' => $e->getMessage(),
            ],
        ];
    }
}


/**
 * Change Package (Upgrade Plan)
*/
function smarterwgsmail_ChangePackage($params) {
    try {

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
    
        if(isset($params["configoption3"]) && $params["configoption3"] == "default")
            $params["configoption3"] = "";
    
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
                if($allocatedExchange == -1)
                    $allocatedExchange = 0;
                if($allocatedEas == -1)
                    $allocatedEas = 0;
                if($allocatedMapi == -1)
                    $allocatedMapi = 0;
                $exch = intval($params["configoptions"]["accounts_exchange"]);
                if($exch < 0)
                    $allocatedExchange = abs($exch);
                else 
                    $allocatedExchange += $exch;
            }
    
        }
    
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
    
        if($maxUsers < 1)
            return "Error: User count must be greater than 0.";
    
    
        $allocatedEas = $allocatedEas + $allocatedExchange;
        $allocatedMapi = $allocatedMapi + $allocatedExchange;
    
    
        if($allocatedEas > -1)
            $allocatedEasManagement = true;
        if($allocatedMapi > -1)
            $allocatedMapiManagement = true;
        if($maxDomainAliases > -1)
            $domainAliasManagement = true;
        if($maxMailboxSize == 0 && $maxDomainSize > 0)
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
    
    
        // Domain settings
        $inputData2 = array(
            'domainSettings' => array(
                'hostname' => $hostname, //
                'mainDomainAdmin' => $params["username"], //
                'outgoingIP'=> $params["configoption2"], //
                'maxAliases' => $maxAliases, //
                'maxUsers' => $maxUsers, //
                'maxSize' => $maxDomainSize*1024 * 1024, //
                'maxLists' => $maxMailingLists, //
    
                'activeDirectoryIntegration' => (($params["configoption11"] == "on") ? true : false),
                'customLoginDisplay' => (($params["configoption12"] == "on") ? true : false),
                'enableMailForwarding'=> (($params["configoption13"] == "on") ? true : false),
                'enableSmtpAccounts'=> (($params["configoption14"] == "on") ? true : false),
                'enableXmpp'=> (($params["configoption15"] == "on") ? true : false),
                'enableDisposableAddresses' => (($params["configoption16"] == "on") ? true : false),
                'enableFileStorage' => (($params["configoption17"] == "on") ? true : false),
                'sharedGlobalAddressList' => (($params["configoption18"] == "on") ? true : false),
                'webConferencing' => (($params["configoption19"] == "on") ? true : false),
                'twoFactorSettings' => array(
                    'setting' => (($params["configoption20"] == "on") ? "1" : "0"),
                ),
                'maxActiveSyncAccounts' => $allocatedEas,
                'maxMapiEwsAccounts' => $allocatedMapi,
                'maxDomainAliases' => $maxDomainAliases,
                'enableActiveSyncAccountManagement' => ($allocatedEasManagement ? true : false),
                'enableMapiEwsAccountManagement' => ($allocatedMapiManagement ? true : false),
                'allowUserSizeChanging' => ($maxMailboxSizeManagement ? true : false),
                'showDomainAliasMenu' => ($domainAliasManagement ? true : false),
                'showListMenu' => ($mailingListManagement ? true : false)
            )
        );
    
        $domainSettings_res = $helper->sysadmin_domainSettings($inputData2);
        if($domainSettings_res['httpcode'] != 200 && ($domainSettings_res['result']['success'] != 1 || $domainSettings_res['result']['success'] != true)) {
            
            logActivity("Removing domain, failed to apply settings correctly", 0);

            if(isset($domainSettings_res['result']['message']) && $domainSettings_res['result']['message'] != "") {
                return $domainSettings_res['result']['message'];
            } else {
                return "An error has occurred.";
            }
        }
    
        //User defaults
        $inputData3 = [
            'maxMailboxSize' => $maxMailboxSize*1024 * 1024,
            'services' => new stdClass()
        ];
        
        // Post4
        $userDefaults_res = $helper->domain_userDefault($inputData3);

        if($userDefaults_res['code'] != 200 && ($userDefaults_res['result']['success'] != 1 || $userDefaults_res['result']['success'] != true)) {

        } else {

            // Propagate Settings
            $inputData4 = [
                'globalUpdate' => [
                    ['userField' => 'MailboxSize', 'longValue' => $maxMailboxSize*1024 * 1024],
                ],
                'emails' => ["*@".$params["domain"]]
            ];

            // Post5
            $propagateSettings_res = $helper->domain_propagateSettings($inputData4);
        }


        return 'success';

    } catch(Exception $e) {
        logActivity("Error to upgrade the plan for SmarterWgsMail, service #".$params['serviceid'].". Error: ".$e->getMessage());
    }


}
