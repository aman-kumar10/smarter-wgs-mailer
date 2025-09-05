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

    /* ******* USER ******* */
    // Add User Form
    if ($formAction == 'addUser') {
        $userData = $helper->managementAddUserPassReq();

        if (is_array($userData)) {
            if (!empty($userData)) {
                $html = '<style>
                        #btn-submit:disabled {
                            color: #000;
                            cursor: not-allowed;
                        }
                    </style>
                <div class="custom-popup" style="display: block;">
                    <div class="custom-popup-content"  style="text-align: left;">
                        <div class="management-form">
                            <h2 style="font-size: 21px;">Add SmarterMail User</h2>
                            <form method="post" action="" id="addSmarterMailUser" style="text-align: left;">
                                <div class="form-group">
                                    <label for="username" style="font-weight: 600;">Username</label>
                                    <input class="form-control" type="text" id="username" name="username" onkeyup="checkPasswordReqs()">
                                </div>

                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <label class="input-group-text" for="authtype">Authentication Mode</label>
                                    </div>
                                    <select class="custom-select" name="authtype" id="authtype" onchange="changedAuthSettings()">
                                        <option value="0" selected="selected">SmarterMail</option>
                                        <option value="1">Active Directory</option>
                                    </select>
                                </div>

                                <div class="form-group sm-auth">
                                    <label for="password" style="font-weight: 600;">Password</label>
                                    <input class="form-control" type="text" id="password" name="password" onkeyup="checkPasswordReqs()">
                                    <div class="alert alert-warning">
                                        <div><strong>Password Requirements</strong></div>
                                        ' . ($userData["reqCapital"] ? '<div id="req-capital" class="req-hide">Contain one UPPERCASE letter</div>' : '') . '
                                        ' . ($userData["reqLower"] ? '<div id="req-lower" class="req-hide">Contain one lowercase letter</div>' : '') . '
                                        ' . ($userData["reqNotUsername"] ? '<div id="req-not-username" class="req-hide">Does not match your username</div>' : '') . '
                                        ' . ($userData["reqNumber"] ? '<div id="req-number" class="req-hide">Contain any number 0 through 9</div>' : '') . '
                                        ' . ($userData["reqSymbol"] ? '<div id="req-symbol" class="req-hide">Contain any special character such as #, @, &, etc.</div>' : '') . '
                                        <div id="req-length">Is at least ' . (int) $userData["reqLength"] . ' characters long</div>
                                    </div>
                                </div>

                                <div class="form-group sm-auth">
                                    <label for="displayname" style="font-weight: 600;">Display Name</label>
                                    <input class="form-control" type="text" id="displayname" name="displayname">
                                </div>

                                <div class="form-group ad-auth" style="display:none;">
                                    <label for="adusername" style="font-weight: 600;">AD Username</label>
                                    <input class="form-control" type="text" id="adusername" name="adusername">
                                </div>

                                <div class="form-group ad-auth" style="display:none;">
                                    <label for="addomain" style="font-weight: 600;">AD Domain</label>
                                    <input class="form-control" type="text" id="addomain" name="addomain">
                                </div>

                                <div class="form-group">
                                    <label for="mailboxsize" style="font-weight: 600;">Mailbox Size in MB (0 = unlimited)</label>
                                    <input class="form-control" type="number" id="mailboxsize" name="mailboxsize" min="0" style="width: 150px">
                                </div>

                                <div class="form-group row sm-auth">
                                    <div class="col-sm-10">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="resetonlogincheckbox" name="resetonlogincheckbox">
                                            <label class="form-check-label" for="resetonlogincheckbox">Reset Password On Login</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-10">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="domainadmincheckbox" name="domainadmincheckbox">
                                            <label class="form-check-label" for="domainadmincheckbox">Domain Administrator</label>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" name="id" value="' . (int) $serviceId . '" />
                                <input type="hidden" name="modop" value="custom" />
                                <input type="hidden" name="formAction" value="createsmartermailuser" />
                                <input id="btn-submit" style="position: unset;" class="btn btn-primary mgmt-form-btn" type="submit" value="Add User" disabled />
                            </form>
                        </div>
                    </div>
                </div>

                    <script type="text/javascript">
                        document.getElementById("authtype").value = "0";
                        const passElement = document.getElementById("password");
                        let reqCapital = ' . ($userData["reqCapital"] ? "true" : "false") . ';
                        let reqCapitalElem = document.getElementById("req-capital");
                        let reqLower = ' . ($userData["reqLower"] ? "true" : "false") . ';
                        let reqLowerElem = document.getElementById("req-lower");
                        let reqLength = ' . (int) $userData["reqLength"] . ';
                        let reqLengthElem = document.getElementById("req-length");
                        let reqNotUsername = ' . ($userData["reqNotUsername"] ? "true" : "false") . ';
                        let reqNotUsernameElem = document.getElementById("req-not-username");
                        let reqNumber = ' . ($userData["reqNumber"] ? "true" : "false") . ';
                        let reqNumberElem = document.getElementById("req-number");
                        let reqSymbol = ' . ($userData["reqSymbol"] ? "true" : "false") . ';
                        let reqSymbolElem = document.getElementById("req-symbol");

                        changedAuthSettings();
                        function changedAuthSettings(){
                            let authMode = document.getElementById("authtype").value;
                            let smAuthElems = document.getElementsByClassName("sm-auth");
                            let adAuthElems = document.getElementsByClassName("ad-auth");                
                            for(let i = 0; i < smAuthElems.length; i++){
                                smAuthElems[i].style.display = (authMode == "0") ? null : "none";
                            }
                            for(let i = 0; i < adAuthElems.length; i++){
                                adAuthElems[i].style.display = (authMode == "1") ? null : "none";
                            }
                            checkPasswordReqs();
                        }

                        function checkPasswordReqs(){
                            let authMode = document.getElementById("authtype").value;
                            if(authMode == "1"){
                                document.getElementById("btn-submit").disabled = false;
                                return;
                            }

                            if(reqCapital && reqCapitalElem){
                                reqCapitalElem.className = passElement.value.match(/[A-Z]/) ? "req-met" : "req-not-met";
                            }
                            if(reqLower && reqLowerElem){
                                reqLowerElem.className = passElement.value.match(/[a-z]/) ? "req-met" : "req-not-met";
                            }
                            if(reqLength && reqLengthElem){
                                reqLengthElem.className = passElement.value.length >= reqLength ? "req-met" : "req-not-met";
                            }
                            if(reqNotUsername && reqNotUsernameElem){
                                reqNotUsernameElem.className = passElement.value != document.getElementById("username").value ? "req-met" : "req-not-met";
                            }
                            if(reqSymbol && reqSymbolElem){
                                reqSymbolElem.className = passElement.value.match(/[^a-zA-Z0-9]/) ? "req-met" : "req-not-met";
                            }
                            if(reqNumber && reqNumberElem){
                                reqNumberElem.className = passElement.value.match(/[0-9]/) ? "req-met" : "req-not-met";
                            }

                            document.getElementById("btn-submit").disabled = document.getElementsByClassName("req-not-met").length > 0;
                        }
                    </script>';
            } else {
                $html = '<div class="alert alert-warning">No data found</div>';
            }
        } elseif (is_string($userData) && !empty($userData)) {
            $html = '<div class="alert alert-info">' . htmlspecialchars($userData) . '</div>';
        } else {
            $html = '<div class="alert alert-warning">No data found</div>';
        }
    }

    // Handle Add User form submit
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

    // Get user edit popup
    if ($formAction === 'getUserEditPopup') {
        
        $domainUserGet = $helper->getdomainUserData(['email' => $whmcs->get_req_var('userName')."@".$params['domain']]);

        if(is_array($domainUserGet) && !empty($domainUserGet)) {
            $html = '
                <!--  Edit Popup  -->
                <div class="custom-popup" style="display: block;">
                    <div class="custom-popup-content"  style="text-align: left;">
                        <div class="custom-popup-header"  style="text-align: center !important; font-size: 17px; font-weight: 600;">
                            Edit User - ' . htmlspecialchars($domainUserGet['userName']) . '
                            <span class="close-popup">&times;</span>
                        </div>
                        <div class="custom-popup-body">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input class="form-control" type="text" id="username" 
                                        name="username" 
                                        value="' . htmlspecialchars($domainUserGet['userName']) . '">
                                </div>

                                <div class="form-group">
                                    <label for="displayname">Display Name</label>
                                    <input class="form-control" type="text" id="displayname" 
                                        name="displayname" 
                                        value="' . htmlspecialchars($domainUserGet['fullName']) . '">
                                </div>

                                <div class="form-group">
                                    <label for="password">New Password</label>
                                    <input class="form-control" type="password" id="password" name="password">
                                </div>

                                <div class="form-group">
                                    <label for="mailboxsize">Mailbox Size (MB)</label>
                                    <input class="form-control" type="number" id="mailboxsize" 
                                        name="mailboxsize" min="0" 
                                        value="' . htmlspecialchars($domainUserGet['maxMailboxSize'] / 1024 / 1024) . '" 
                                        style="width: 150px">
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="domainadmin" 
                                        name="domainadmin" ' . (!empty($domainUserGet['securityFlags']['isDomainAdmin']) ? 'checked' : '') . '>
                                    <label class="form-check-label" for="domainadmin">
                                        Domain Administrator
                                    </label>
                                </div>

                                <input type="hidden" name="selectuser" value="' . htmlspecialchars($domainUserGet['userName']) . '">
                                <input type="hidden" name="modop" value="custom">
                                <input type="hidden" name="formAction" value="savechangessmartermailuser">

                                <div class="mt-3">
                                    <input class="btn btn-primary edit-domain-user" type="submit"  style="right: unset; margin-bottom: 34px; position: unset;" value="Save Changes">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            ';
        } else {
            $html = '<div class="alert alert-danger">' . htmlspecialchars($domainUserGet) . '</div>';
        }
    }

    // Handle edit User form submit
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


    /* ******* ALIAS ******* */
    // Add Aliases Form
    if ($formAction == 'addAlias') {
        $userData = $helper->accountsListSearch('aliases');

        $checkedAllowSending = !empty($userData['allowSending']) && $userData['allowSending'] == 1 ? "checked='checked'" : "";
        $checkedInternalOnly = !empty($userData['internalOnly']) && $userData['internalOnly'] == 1 ? "checked='checked'" : "";
        $checkedShowInGAL   = (isset($userData['hideFromGAL']) && $userData['hideFromGAL'] == 0) ? "checked='checked'" : "";

        $html = '
            <style>
                #btn-submit:disabled {
                    color: #000;
                    cursor: not-allowed;
                    border: 1px solid #efd68e;
                }
            </style>
            <div class="custom-popup" style="display: block;">
                <div class="custom-popup-content"  style="text-align: left;">
                    <div class="management-form">
                        <h2 style="font-size: 21px;">Add SmarterMail User Alias</h2>
                        <form method="post" action="" id="addSmarterMailAlias" style="text-align: left;">

                            <div class="form-group">
                                <label for="newaliasname" style="font-weight: 600;">Alias Name</label>
                                <input class="form-control" type="text" id="newaliasname" name="newaliasname">
                            </div>

                            <div class="form-group">
                                <label for="displayname" style="font-weight: 600;">Display Name</label>
                                <input class="form-control" type="text" name="displayname" value="' . htmlspecialchars($userData['displayName'] ?? '') . '">
                            </div>

                            <div>
                                Email Addresses
                                <p style="font-size: 12px; float: right;">One address per line</p>
                                <textarea cols="36" rows="15" name="newaliasemailaddress" style="overflow: auto; resize: none; width: 100%; padding: 10px;"></textarea>
                            </div>

                            <div class="form-group row">
                                <div class="col-sm-10">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="allowsending" ' . $checkedAllowSending . ' />
                                        <label class="form-check-label" for="allowsending">
                                            Alias can be used as a from address in webmail
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-sm-10">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="internalonly" ' . $checkedInternalOnly . ' />
                                        <label class="form-check-label" for="internalonly">
                                            Internal Only
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-sm-10">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="showingal" ' . $checkedShowInGAL . ' />
                                        <label class="form-check-label" for="showingal">
                                            Show in Global Address List
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="id" value="' . $serviceId . '" />
                            <input type="hidden" name="modop" value="custom" />
                            <input type="hidden" name="formAction" value="createsmartermailalias" />
                            <input class="btn btn-primary mgmt-form-btn" style="position: unset;" type="submit" value="Add Alias" />
                        </form>
                    </div>
                </div>
            </div>
        ';
    }

    // Handle Add Alias form submit
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

    // Get alias edit popup
    if ($formAction === 'getAliasEditPopup') {
        
        $domainAliasGet = $helper->getdomainAliasData($whmcs->get_req_var('userName'));

        if(is_array($domainAliasGet) && !empty($domainAliasGet)) {

            $allowSending = ($domainAliasGet['allowSending'] == 1) ? 'checked="checked"' : '';
            $internalOnly = ($domainAliasGet['internalOnly'] == 1) ? 'checked="checked"' : '';
            $hideFromGAL  = ($domainAliasGet['hideFromGAL'] == 1) ? 'checked="checked"' : '';

            $html = '
                <!-- Edit alias popup -->
                <div class="custom-popup"  style="display: block;">
                    <div class="custom-popup-content" style="text-align: left;">
                        <div class="custom-popup-header" style="text-align: center !important; font-size: 17px; font-weight: 600;">
                            Edit Alias - ' . htmlspecialchars($aliasHeading) . '
                            <span class="close-popup">&times;</span>
                        </div>
                        <div class="custom-popup-body">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label for="aliasname">Alias Name (username)</label>
                                    <input class="form-control" type="text" name="aliasname" value="' . htmlspecialchars($domainAliasGet['name']) . '">
                                </div>

                                <div class="form-group">
                                    <label for="displayname">Display Name</label>
                                    <input class="form-control" type="text" name="displayname" value="' . htmlspecialchars($domainAliasGet['displayName']) . '">
                                </div>

                                <div class="form-group">
                                    <label for="aliasemailaddress">Alias Email Addresses</label>
                                    <p style="font-size: 12px; float: right;">One address per line</p>
                                    <textarea cols="36" rows="10" name="aliasemailaddress" style="overflow: auto; resize: none; width: 100%; padding: 10px;">' . htmlspecialchars($aliasTargetList) . '</textarea>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-10">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="allowsending" ' . $allowSending . '/>
                                            <label class="form-check-label" for="allowsending">
                                                Alias can be used as a from address in webmail
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-10">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="internalonly" ' . $internalOnly . '/>
                                            <label class="form-check-label" for="internalonly">
                                                Internal Only
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-10">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="showingal" ' . $hideFromGAL . '/>
                                            <label class="form-check-label" for="showingal">
                                                Show in Global Address List
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" name="selectuser" value="' . htmlspecialchars($domainAliasGet['name']) . '" />
                                <input type="hidden" name="modop" value="custom" />
                                <input type="hidden" name="formAction" value="savechangessmartermailalias" />
                                <input class="btn btn-primary edit-domain-alias" type="submit" style="right: unset; margin-bottom: 34px; position: unset;" value="Save Changes" />
                            </form>
                        </div>
                    </div>
                </div>
            ';
        } else {
            $html = '<div class="alert alert-danger">' . htmlspecialchars($domainAliasGet) . '</div>';
        }
    }

    // Handle edit alias form submit
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


    // Add Mail List Form
    if ($formAction == 'addMail') {
        $userData = $helper->accountsListSearch('users');

        if(is_array($userData['responseData']) && !empty($userData['responseData'])) {
            $options = '';
            foreach($userData['responseData'] as $data) {
                $options .= '<option value="'.$data['userName'].'">'.$data['userName'].'</option>';
            }
            $html = '
                <style>
                    #btn-submit:disabled {
                        color: #000;
                        cursor: not-allowed;
                        border: 1px solid #efd68e;
                    }
                </style>
                <div class="custom-popup" style="display: block;">
                    <div class="custom-popup-content"  style="text-align: left;">
                        <div class="management-form">
                            <h2 style="font-size: 21px;">Add Mailing List</h2>
                            <form method="post" action="" id="addMailList" style="text-align: left;">
                                <div class="form-group">
                                    <label for="listname" style="font-weight: 600;">Mailing List Name (username)</label>
                                    <input class="form-control" type="text" id="listname" name="listname">
                                </div>

                                <div class="form-group">
                                    <label for="moderatorname" style="font-weight: 600;">Moderator Username</label>			
                                    <select name="moderatorname" id="moderatorname" class="form-control">
                                        <option value="" disabled selected>--Select a user--</option>
                                        '.$options.'
                                    </select>
                                </div>

                                <input type="hidden" name="formAction" value="createsmartermailmailinglist" />
                                <input class="btn btn-primary mgmt-form-btn" style="position: unset;" type="submit" value="Add Mailing List" />
                            </form>
                        </div>
                    </div>
                </div>
            ';
        } else {
            $html = '<div class="alert alert-danger">' . htmlspecialchars($userData['responseData']) . '</div>';
        }
    }

    // add mailing list form submit
    if ($formAction === 'createsmartermailmailinglist') {

        $moderatorAddress = $whmcs->get_req_var('moderatorname');

        if (strpos($moderatorAddress, '@') === false) {
            $moderatorAddress .= '@' . $params["domain"];
        }

        $inputData = [
            'listAddress' => $whmcs->get_req_var('listname'),
            'moderatorAddress' => $moderatorAddress
        ];

        $mailListAdd = $helper->mailListAdd($inputData);
        
        if (!empty($mailListAdd['status']) && $mailListAdd['status'] === 'success') {
            $html = '<div class="alert alert-success">Mail list added successfully</div>';
        } else {
            $html = '<div class="alert alert-danger">' . htmlspecialchars($mailListAdd['message']) . '</div>';
        }
    }

    // delete mailing list
    if ($formAction === 'domainMailDelete') {

        $inputData = [
            'input' => ""
        ];

        $mailDelete = $helper->domainMailDelete($inputData, $whmcs->get_req_var('userName'));
        
        if (!empty($mailDelete['status']) && $mailDelete['status'] === 'success') {
            $html = '<div class="alert alert-success">Mail list deleted successfully</div>';
        } else {
            $html = '<div class="alert alert-danger">' . htmlspecialchars($mailDelete['message']) . '</div>';
        }
    }

    // ******* Login to edit mailing list *******
    // if($formAction === 'loginToEditMail') {

    //     $getMailLoginURL = $helper->getMailLoginURL($whmcs->get_req_var('mlid'));

    //     if (!empty($getMailLoginURL['status']) && $getMailLoginURL['status'] === 'success') {
    //         $html = '<div class="alert alert-success">Mail list deleted successfully</div>';
    //     } else {
    //         $html = '<div class="alert alert-danger">' . htmlspecialchars($getMailLoginURL['message']) . '</div>';
    //     }
    // }


    // Manage EAS License

    if($formAction === 'saveeaslicensechanges') {

        $licensedMailboxes = $helper->licensedMailboxes();

        if(is_array($licensedMailboxes) && !empty($licensedMailboxes)) {
            $activeSyncUsersProc = array();
            foreach ($licensedMailboxes as $lu)
            {
                $activeSyncUsersProc[$lu['emailAddress']] = array(
                    'emailAddress' => $lu['emailAddress'],
                    'enabledBefore' => true,
                    'enabledAfter' => false
                );
            }

            foreach ($whmcs->get_req_var('licenseuser') as $lu)
            {
                $luEmail = $lu.'@'.$params['domain'];
                if(isset($activeSyncUsersProc[$luEmail])){
                    $activeSyncUsersProc[$luEmail]['enabledAfter'] = true;
                }
                else{
                    $activeSyncUsersProc[$luEmail] = array(
                        'emailAddress' => $luEmail,
                        'enabledBefore' => false,
                        'enabledAfter' => true
                    );
                }
            }


            $inputData = array();

            foreach ($activeSyncUsersProc as $lu)
            {
                if($lu['enabledBefore'] && $lu['enabledAfter'])
                    continue;
                if(!$lu['enabledBefore'] && !$lu['enabledAfter'])
                    continue;

                array_push($inputData, array(
                    'emailAddress' => $lu['emailAddress'],
                    'isActive' => $lu['enabledAfter']
                ));
            }

            $activeSyncPatch = $helper->activeSyncPatch($inputData);
            if($activeSyncPatch['status'] === 'success') {
                $html = '<div class="alert alert-danger">Save changes successfully</div>';
            } else {
                $html = '<div class="alert alert-danger">' . htmlspecialchars($activeSyncPatch['message']) . '</div>';
            }
            
        } else {
            $html = '<div class="alert alert-danger">' . htmlspecialchars($licensedMailboxes['message']) . '</div>';
        }
    }

    // Manage AWS License
    if($formAction === 'savemapilicensechanges') {

        $licensedMailboxes = $helper->licensedMailboxesEWS();

        if(is_array($licensedMailboxes) && !empty($licensedMailboxes)) {
            $usersProc = array();
            foreach ($licensedMailboxes['data']['mapiEwsAccounts'] as $lu)
            {
                $usersProc[$lu['emailAddress']] = array(
                    'emailAddress' => $lu['emailAddress'],
                    'enabledBefore' => true,
                    'enabledAfter' => false
                );
            }

            foreach ($_POST['licenseuser'] as $lu)
            {
                $luEmail = $lu.'@'.$params['domain'];
                if(isset($usersProc[$luEmail])){
                    $usersProc[$luEmail]['enabledAfter'] = true;
                }
                else{
                    $usersProc[$luEmail] = array(
                        'emailAddress' => $luEmail,
                        'enabledBefore' => false,
                        'enabledAfter' => true
                    );
                }
            }

            $inputData = array();
            foreach ($usersProc as $lu)
            {
                if($lu['enabledBefore'] && $lu['enabledAfter'])
                    continue;
                if(!$lu['enabledBefore'] && !$lu['enabledAfter'])
                    continue;

                array_push($inputData, array(
                    'emailAddress' => $lu['emailAddress'],
                    'isActive' => $lu['enabledAfter']
                ));
            }

            $activeSyncPatch = $helper->activeSyncPatchEWS($inputData);
            if($activeSyncPatch['status'] === 'success') {
                $html = '<div class="alert alert-danger">Save changes successfully</div>';
            } else {
                $html = '<div class="alert alert-danger">' . htmlspecialchars($activeSyncPatch['message']) . '</div>';
            }
            
        } else {
            $html = '<div class="alert alert-danger">' . htmlspecialchars($licensedMailboxes['message']) . '</div>';
        }
    }



    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
}

http_response_code(400);
echo "Invalid request";
exit;