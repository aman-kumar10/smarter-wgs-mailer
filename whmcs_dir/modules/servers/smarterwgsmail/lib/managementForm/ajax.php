<?php

use WHMCS\ClientArea;
use WHMCS\Service\Service;
use WHMCS\Module\Server\SmarterWgsMail\Helper;

require_once dirname(__DIR__, 5) . '/init.php';
require_once dirname(__DIR__, 5) . '/includes/modulefunctions.php';

// Only allow logged-in clients
$ca = new ClientArea();
$ca->requireLogin();

$helper = new Helper();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $whmcs->get_req_var('action') === 'managementFormHandling') {
    $serviceId = (int) $whmcs->get_req_var('serviceid');
    $formAction = preg_replace('/[^a-zA-Z0-9_-]/', '', $whmcs->get_req_var('formAction'));

    if (!$serviceId) {
        http_response_code(400);
        echo "Service ID missing";
        exit;
    }

    // Load service
    $service = Service::find($serviceId);
    if (!$service) {
        http_response_code(404);
        echo "Service not found";
        exit;
    }

    $params = ModuleBuildParams($serviceId);

    $helper = new Helper($params);

    $domainData = $helper->sysadmin_getDomainData();

    // Handle Add User
    if ($formAction === 'createsmartermailuser') {

        $inputData = [
            'userData' => [
                'userName' => $whmcs->get_req_var('username'),
                'fullName' => $whmcs->get_req_var('displayname'),
                'securityFlags' => [
                    'isDomainAdmin' => (($whmcs->get_req_var('domainadmincheckbox') == "on") ? true : false),
                    'authType' => (($whmcs->get_req_var('authtype') == "1") ? 1 : 0)
                ]
            ],
        ];

        if($whmcs->get_req_var('authtype') == "1"){
            $inputData['userData']['securityFlags']['authenticatingWindowsDomain'] = $whmcs->get_req_var('addomain');
            $inputData['userData']['adUsername'] = $whmcs->get_req_var('adusername');
        } else {
            $inputData['userData']['password'] = $whmcs->get_req_var('password') == "" ? null : $whmcs->get_req_var('password');
            $inputData['userData']['isPasswordExpired'] = ($whmcs->get_req_var('resetonlogincheckbox') == "on") ? true : false;
        }

        if($domainData['settings']['allowUserSizeChanging']) {
            $inputData['userMailSettings'] = array('maxSize' => $whmcs->get_req_var('mailboxsize') * 1024 * 1024);
        }

        $userData = $helper->domainUserPut($inputData);

        if (!empty($userData['status']) && $userData['status'] === 'success') {
            $html = '<div class="alert alert-success">User created successfully</div>';
        } else {
            $msg = !empty($userData['response']) ? $userData['response'] : 'User creation failed';
            $html = '<div class="alert alert-danger">' . htmlspecialchars($msg) . '</div>';
        }
    }

    // Handle Add Alias
    if ($formAction === 'createsmartermailalias') {
        $inputData = [
            'alias' => [
                'name' => $whmcs->get_req_var('newaliasname') ?? '',
                'displayName' => $whmcs->get_req_var('displayname') ?? '',
                'allowSending' => !empty($whmcs->get_req_var('allowsending')),
                'enableForXmpp' => false,
                'hideFromGAL' => !($whmcs->get_req_var('showingal')),
                'includeAllDomainUsers' => false,
                'internalOnly' => !empty($whmcs->get_req_var('internalonly')),
                'aliasTargetList' => !empty($whmcs->get_req_var('newaliasemailaddress'))
                    ? preg_split('/\r\n|\r|\n/', trim($whmcs->get_req_var('newaliasemailaddress')))
                    : []
            ]
        ];

        $userData = $helper->domainAliasPut($inputData);

        if (!empty($userData['status']) && $userData['status'] === 'success') {
            $html = '<div class="alert alert-success">User Alias added successfully</div>';
        } else {
            $msg = !empty($userData['response']) ? $userData['response'] : 'Alias creation failed';
            $html = '<div class="alert alert-danger">' . htmlspecialchars($msg) . '</div>';
        }
    }


    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
}

http_response_code(400);
echo "Invalid request";
exit;