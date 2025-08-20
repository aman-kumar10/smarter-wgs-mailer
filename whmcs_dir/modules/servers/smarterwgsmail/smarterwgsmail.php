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