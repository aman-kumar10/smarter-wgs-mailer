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

        $userData = $helper->domainUserPut($inputData, 'add');

        if (!empty($userData['status']) && $userData['status'] === 'success') {
            $html = '<div class="alert alert-success">User created successfully</div>';
        } else {
            if(!empty(trim($userData['response']))) {
                if(strpos($userData['response'], 'LIMIT_EXCEEDED')) {
                    // $html = '<div class="alert alert-danger">' . htmlspecialchars($userData['response']) . ' <a href="upgrade.php?type=package&id='.$serviceId.'" class="btn btn-success" style="background: green;">Upgrade Plan?</a></div>';
                    $html = '<div class="alert alert-danger">' . htmlspecialchars($userData['response']) . '</div>';
                }
            } else {
                $html = '<div class="alert alert-danger">User creation failed</div>';
            }
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

        $userData = $helper->domainAliasPut($inputData, 'add');

        if (!empty($userData['status']) && $userData['status'] === 'success') {
            $html = '<div class="alert alert-success">User Alias added successfully</div>';
        } else {
            $msg = !empty($userData['response']) ? $userData['response'] : 'Alias creation failed';
            $html = '<div class="alert alert-danger">' . htmlspecialchars($msg) . '</div>';
        }
    }


    // Handle edit User
    if ($formAction === 'savechangessmartermailuser') {

        $inputData = [
            'email' => $whmcs->get_req_var('selectuser'),
            'userData' => [
                'fullName' => $whmcs->get_req_var('displayname'),
                'maxMailboxSize' => $whmcs->get_req_var('mailboxsize') * 1024 * 1024,
                'securityFlags' => [
                    'isDomainAdmin' => (($whmcs->get_req_var('domaincheckbox') == "on") ? true : false),
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

        $userData = $helper->domainUserPut($inputData, 'edit');

        if (!empty($userData['status']) && $userData['status'] === 'success') {
            $html = '<div class="alert alert-success">User updated successfully</div>';
        } else {
            if(!empty(trim($userData['response']))) {
                if(strpos($userData['response'], 'LIMIT_EXCEEDED')) {
                    $html = '<div class="alert alert-danger">' . htmlspecialchars($userData['response']) . '</div>';
                }
            } else {
                $html = '<div class="alert alert-danger">User updation failed</div>';
            }
        }
    }

    // Handle edit alias
    if ($formAction === 'savechangessmartermailalias') {

        $inputData = [
            'oldName' => $whmcs->get_req_var('selectuser'),
            'alias' => [
                'name' => $whmcs->get_req_var('aliasname'),
                'displayName' => $whmcs->get_req_var('displayname'),
                'allowSending' => (($whmcs->get_req_var('allowsending') == "on") ? true : false),
                'hideFromGAL' => !(($whmcs->get_req_var('showingal') == "on") ? true : false),
                'internalOnly' => (($whmcs->get_req_var('internalonly') == "on") ? true : false),
                'aliasTargetList' => explode("\r\n", $whmcs->get_req_var('aliasemailaddress'))
            ],
        ];

        $response = $helper->domainAliasPut($inputData, 'edit');

        if (!empty($response['status']) && $response['status'] === 'success') {
            $html = '<div class="alert alert-success">Alias updated successfully</div>';
        } else {
            if(!empty(trim($response['response']))) {
                if(strpos($response['response'], 'LIMIT_EXCEEDED')) {
                    $html = '<div class="alert alert-danger">' . htmlspecialchars($response['response']) . '</div>';
                }
            } else {
                $html = '<div class="alert alert-danger">Alias updation failed</div>';
            }
        }
    }


    // User or alise delete
    if ($formAction === 'domainUserDelete' || $formAction === 'domainAliasDelete') {
        $getUser = [
            'email' => $whmcs->get_req_var('userName'). '@' . $params['domain']
        ];

        $deleted = 'Alias';
        if($formAction === 'domainUserDelete') {
            $domainUserGet = $helper->getdomainUserData($getUser);
            if (($domainUserGet['securityFlags']) && $domainUserGet['securityFlags']['isDomainAdmin']) {
                $html = '<div class="alert alert-warning">This is the Primary Domain Administrator account and can not be deleted.
                    <p style="text-align: center; color: #664d03;">NOTE: Deleting a user will remove all data and can not be reversed.</p>
                </div>';
            } 
            $deleted = 'User';
        }

        $inputData = [
            'input' => [$whmcs->get_req_var('userName')]
        ];


        $domainUserDelete = $helper->domainUserAliasDelete($inputData);
        
        if (!empty($domainUserDelete['status']) && $domainUserDelete['status'] === 'success') {
            $html = '<div class="alert alert-success">'.$deleted.' deleted successfully</div>';
        } else {
            $html = '<div class="alert alert-danger">' . htmlspecialchars($domainUserDelete['message']) . '</div>';
        }
    }



    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
}

http_response_code(400);
echo "Invalid request";
exit;