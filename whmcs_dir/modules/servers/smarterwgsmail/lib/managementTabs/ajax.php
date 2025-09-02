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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $whmcs->get_req_var('action') === 'managementTabRequest') {
    $serviceId = (int) $whmcs->get_req_var('serviceid');
    $tab = preg_replace('/[^a-zA-Z0-9_-]/', '', $whmcs->get_req_var('tab'));

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

    $userData   = $helper->accountsListSearch('users');

    // Get Users
    if ($tab == 'userAccount') {

        $userData = $helper->accountsListSearch('users');

        if (!empty($userData['responseData']) && is_array($userData['responseData'])) {
            $html .= '<h3 style="margin-top:15px;">Users</h3>';

            foreach ($userData['responseData'] as $index => $user) {
                if (!is_array($user)) continue;

                $userHeading = !empty($user['displayName']) ? $user['displayName'] : 'Unnamed User';

                // Get user-specific data for editing
                $domainUserGet = $helper->getdomainUserData(['email' => $user['userName']."@".$params['domain']]);

                $formattedData = [];
                foreach ($user as $label => $value) {
                    if (is_array($value) || is_object($value)) continue;
                    $formattedData[$helper->labelFormat($label)] = $value;
                }

                $html .= '
                <div class="card mb-3 user-card" style="border:1px solid #ddd; border-radius:6px;">
                    <div class="card-header" style="background:#f8f9fa; font-weight:bold;">
                        ' . htmlspecialchars($userHeading) . '
                        <div style="float:right;">
                            <button class="btn custom-btn view-user" data-target="userPopup' . $index . '">View</button>
                            <button class="btn custom-btn edit-user" style="background: green !important;" data-target="userEditPopup' . $index . '">Edit</button>
                            <button class="btn custom-btn delete-user" style="background: red !important;" data-target="deletePopup' . $index . '">Delete</button>
                        </div>
                    </div>
                </div>

                <!--  View Popup  -->
                <div id="userPopup' . $index . '" class="custom-popup">
                    <div class="custom-popup-content">
                        <div class="custom-popup-header">
                            ' . htmlspecialchars($userHeading) . ' - Details
                            <span class="close-popup">&times;</span>
                        </div>
                        <div class="custom-popup-body">';

                foreach ($formattedData as $label => $value) {
                    if (is_null($value) || trim((string) $value) === '') continue;

                    if ($value === true) {
                        $displayValue = '<i class="fa fa-check" style="color: #02af02" aria-hidden="true"></i>';
                    } elseif ($value === false) {
                        $displayValue = '<i class="fa fa-times" style="color: red" aria-hidden="true"></i>';
                    } else {
                        $displayValue = htmlspecialchars((string) $value);
                    }

                    $html .= '
                            <div class="row mb-2">
                                <div class="col-sm-5 text-left"><strong>' . htmlspecialchars($label) . '</strong></div>
                                <div class="col-sm-7 text-left">' . $displayValue . '</div>
                            </div>';
                }

                $html .= '
                        </div>
                    </div>
                </div>

                <!--  Edit Popup  -->
                <div id="userEditPopup' . $index . '" class="custom-popup">
                    <div class="custom-popup-content"  style="text-align: left;">
                        <div class="custom-popup-header"  style="text-align: center !important; font-size: 17px; font-weight: 600;">
                            Edit User - ' . htmlspecialchars($userHeading) . '
                            <span class="close-popup">&times;</span>
                        </div>
                        <div class="custom-popup-body">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label for="username' . $index . '">Username</label>
                                    <input class="form-control" type="text" id="username' . $index . '" 
                                        name="username" 
                                        value="' . htmlspecialchars($domainUserGet['userName']) . '">
                                </div>

                                <div class="form-group">
                                    <label for="displayname' . $index . '">Display Name</label>
                                    <input class="form-control" type="text" id="displayname' . $index . '" 
                                        name="displayname" 
                                        value="' . htmlspecialchars($domainUserGet['fullName']) . '">
                                </div>

                                <div class="form-group">
                                    <label for="password' . $index . '">New Password</label>
                                    <input class="form-control" type="password" id="password' . $index . '" name="password">
                                </div>

                                <div class="form-group">
                                    <label for="mailboxsize' . $index . '">Mailbox Size (MB)</label>
                                    <input class="form-control" type="number" id="mailboxsize' . $index . '" 
                                        name="mailboxsize" min="0" 
                                        value="' . htmlspecialchars($domainUserGet['maxMailboxSize'] / 1024 / 1024) . '" 
                                        style="width: 150px">
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="domainadmin' . $index . '" 
                                        name="domainadmin" ' . (!empty($domainUserGet['securityFlags']['isDomainAdmin']) ? 'checked' : '') . '>
                                    <label class="form-check-label" for="domainadmin' . $index . '">
                                        Domain Administrator
                                    </label>
                                </div>

                                <input type="hidden" name="selectuser" value="' . htmlspecialchars($domainUserGet['userName']) . '">
                                <input type="hidden" name="id" value="' . (int)$id . '">
                                <input type="hidden" name="modop" value="custom">
                                <input type="hidden" name="formAction" value="savechangessmartermailuser">

                                <div class="mt-3">
                                    <input class="btn btn-primary edit-domain-user" type="submit"  style="right: unset; margin-bottom: 34px; position: unset;" value="Save Changes">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


                <!--  Delete Popup  -->
                <div id="deletePopup' . $index . '" class="custom-popup">
                    <div class="custom-popup-content">
                        <span class="close-popup">&times;</span>
                        <h1 style="margin: 0;"><i class="fas fa-times-circle fa-fw" style="color: red;"></i></h1>
                        <p>Are you sure you want to delete <strong>' . htmlspecialchars($user['userName']) . '</strong>?</p>
                        <button class="btn custom-btn confirm-delete" 
                                data-username="' . htmlspecialchars($user['userName']) . '" 
                                data-type="user">Yes, Delete</button>
                    </div>
                </div>';
            }
        } else {
            $html .= '<div class="alert alert-warning">No users found</div>';
        }
    }

    // Get Aliases
    if ($tab == 'userAliases') {
        $aliasData  = $helper->accountsListSearch('aliases');

        if (!empty($aliasData['responseData']) && is_array($aliasData['responseData'])) {
            $html .= '<h3 style="margin-top:30px;">Aliases</h3>';

            foreach ($aliasData['responseData'] as $aIndex => $alias) {
                if (!is_array($alias)) continue;

                $aliasHeading = !empty($alias['displayName']) ? $alias['displayName'] : 'Unnamed Alias';

                // Get user-specific data for editing
                $domainAliasGet = $helper->getdomainAliasData($alias['userName']);

                $formattedData = [];
                foreach ($alias as $label => $value) {
                    if (is_array($value) || is_object($value)) continue;
                    $formattedData[$helper->labelFormat($label)] = $value;
                }

                $aliasTargetList = '';
                if (is_array($domainAliasGet['aliasTargetList'])) {
                    foreach ($domainAliasGet['aliasTargetList'] as $text) {
                        $aliasTargetList .= $text . "\n";
                    }
                } else {
                    $aliasTargetList = $domainAliasGet['aliasTargetList'];
                }

                $allowSending = ($domainAliasGet['allowSending'] == 1) ? 'checked="checked"' : '';
                $internalOnly = ($domainAliasGet['internalOnly'] == 1) ? 'checked="checked"' : '';
                $hideFromGAL  = ($domainAliasGet['hideFromGAL'] == 1) ? 'checked="checked"' : '';

                $html .= '
                <div class="card mb-3 alias-card" style="border:1px solid #ddd; border-radius:6px;">
                    <div class="card-header" style="background:#f8f9fa; font-weight:bold;">
                        ' . htmlspecialchars($aliasHeading) . '
                        <div style="float:right;">
                            <button class="btn custom-btn view-alias" style="background: #007bff !important;" data-target="aliasPopup' . $aIndex . '">View</button>
                            <button class="btn custom-btn edit-alias" style="background: green !important;" data-target="aliasEditPopup' . $aIndex . '">Edit</button>
                            <button class="btn custom-btn delete-alias" style="background: red !important;" data-target="aliasDeletePopup' . $aIndex . '">Delete</button>
                        </div>
                    </div>
                </div>

                <!-- view popup -->
                <div id="aliasPopup' . $aIndex . '" class="custom-popup">
                    <div class="custom-popup-content">
                        <div class="custom-popup-header">
                            ' . htmlspecialchars($aliasHeading) . ' - Details
                            <span class="close-popup">&times;</span>
                        </div>
                        <div class="custom-popup-body">';

                foreach ($formattedData as $label => $value) {
                    if (is_null($value) || trim((string) $value) === '') {
                        continue;
                    }

                    if ($value === true) {
                        $displayValue = '<i class="fa fa-check" style="color: #02af02" aria-hidden="true"></i>';
                    } elseif ($value === false) {
                        $displayValue = '<i class="fa fa-times" style="color: red" aria-hidden="true"></i>';
                    } elseif (is_numeric($value) && $label && stripos($label, 'bytes') !== false) {
                            $displayValue = $helper->formatSize((float) $value);
                    } else {
                        $displayValue = htmlspecialchars((string) $value);
                    }

                    $html .= '
                            <div class="row mb-2">
                                <div class="col-sm-5 text-left"><strong>' . htmlspecialchars($label) . '</strong></div>
                                <div class="col-sm-7 text-left">' . $displayValue . '</div>
                            </div>';
                }

                $html .= '
                        </div>
                    </div>
                </div>

                <!-- Edit alias popup -->
                <div id="aliasEditPopup' . $aIndex . '" class="custom-popup">
                    <div class="custom-popup-content" style="text-align: left;">
                        <div class="custom-popup-header" style="text-align: center !important; font-size: 17px; font-weight: 600;">
                            Edit Alias - ' . htmlspecialchars($aliasHeading) . '
                            <span class="close-popup">&times;</span>
                        </div>
                        <div class="custom-popup-body">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label for="aliasname">Alias Name (username)</label>
                                    <input class="form-control" type="text" name="aliasname" style="width: 300px" value="' . htmlspecialchars($domainAliasGet['name']) . '">
                                </div>

                                <div class="form-group">
                                    <label for="displayname">Display Name</label>
                                    <input class="form-control" type="text" name="displayname" style="width: 300px" value="' . htmlspecialchars($domainAliasGet['displayName']) . '">
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

                <!-- delete confirm popup -->
                <div id="aliasDeletePopup' . $aIndex . '" class="custom-popup">
                    <div class="custom-popup-content">
                        <span class="close-popup">&times;</span>
                        <h1 style="margin: 0;"><i class="fas fa-times-circle fa-fw" style="color: red;"></i></h1>
                        <p>Are you sure you want to delete <strong>' . htmlspecialchars($alias['aliasEmail']) . '</strong>?</p>
                        <button class="btn custom-btn confirm-delete" 
                                data-username="' . htmlspecialchars($alias['aliasEmail']) . '" 
                                data-type="alias">Yes, Delete</button>
                    </div>
                </div>';
            }
        } else {
            $html .= '<div class="alert alert-warning">No aliases found</div>';
        }
    }

    // Add User Form
    if ($tab == 'addUser') {
        $userData = $helper->managementAddUserPassReq();

        if (is_array($userData)) {
            if (!empty($userData)) {
                $html = '<style>
                        #btn-submit:disabled {
                            color: #000;
                            cursor: not-allowed;
                        }
                    </style>
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

    // Add User Aliases Form
    if ($tab == 'addAlias') {
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
        ';
    }

    // For manage EWS Licenses
    if ($tab == 'manageEwsLicenses') {

        $userData = $helper->accountsListSearch('users');
        $domainSettings = $helper->sysadmin_getDomainSettings();

        $users = [];
        if (!empty($userData['responseData'])) {
            foreach ($userData['responseData'] as $acc) {
                $users[] = $acc;
            }
        }

        if (!empty($users)) {
            $html = '
            <div>
                <h1 style="padding: 10px 50px;"> Manage MAPI/EWS Users </h1>
                <form method="post" action="">
                    <div style="padding: 10px 50px;">

                        <input type="hidden" name="id" value="' . (int)$id . '" />
                        <input type="hidden" name="modop" value="custom" />
                        <input type="hidden" name="a" value="savemapilicensechanges"/>
                        <input class="btn btn-primary" id="save-changes" type="submit" value="Save Changes" disabled />

                        <br>
                        MAPI/EWS Licenses remaining: <span id="license-count-used">0</span>/<span id="license-count-total">0</span>
                        <br><br><br>
                        <div>
                            <button class="btn btn-link" type="button" id="check-all">Check All</button>
                            <button class="btn btn-link" type="button" id="uncheck-all">Uncheck All</button>
                        </div>
                        <ul class="list-group" style="max-height: 400px;overflow-y: auto;user-select: none;">';

            foreach ($users as $value) {
                $checked = !empty($value['isMapiEwsEnabled']) ? 'checked' : '';
                $html .= '
                            <li class="list-group-item check-container">
                                <input class="form-check-input me-1" type="checkbox" name="licenseuser[]" value="' . htmlspecialchars($value['userName']) . '" ' . $checked . '>
                                ' . htmlspecialchars($value['userName']) . '
                            </li>';
            }

            $html .= '
                        </ul>
                    </div>
                </form>
                
                <script type="text/javascript">
                    let saveChangesBtn = document.getElementById("save-changes");
                    let checkAllBtn = document.getElementById("check-all");
                    let uncheckAllBtn = document.getElementById("uncheck-all");
                    let checkboxes = document.querySelectorAll("input[type=\'checkbox\']");
                    let licenseCountUsed = document.getElementById("license-count-used");
                    let licenseCountTotal = document.getElementById("license-count-total");
                    let availableLicenses = ' . (int)$domainSettings['maxMapiEwsAccounts'] . ';
                    let usedLicenses = 0;

                    checkAllBtn.onclick = function(e){
                        e.preventDefault();
                        updateCheckedCount();
                        let checkedCount = parseInt(licenseCountUsed.innerText);
                        for (let i = 0; i < checkboxes.length; i++){
                            if(checkboxes[i].checked) continue;
                            if(checkedCount >= licenseCountTotal.innerText) continue;
                            checkboxes[i].checked = true;
                            checkedCount++;
                        }
                        updateCheckedCount();
                    };

                    uncheckAllBtn.onclick = function(e){
                        e.preventDefault();
                        for (let i = 0; i < checkboxes.length; i++){
                            checkboxes[i].checked = false;
                        }
                        updateCheckedCount();
                    };

                    for (let i = 0; i < checkboxes.length; i++){
                        checkboxes[i].onclick = function(){
                            updateCheckedCount();
                        }
                    }

                    updateCheckedCount(true);

                    function updateCheckedCount(first){
                        if(!first) saveChangesBtn.disabled = false;
                        usedLicenses = 0;
                        for (let i = 0; i < checkboxes.length; i++){
                            if(checkboxes[i].checked) usedLicenses++;
                        }
                        licenseCountUsed.innerText = usedLicenses;
                        if(availableLicenses == 0){
                            licenseCountTotal.innerText = "∞";
                        } else {
                            licenseCountTotal.innerText = availableLicenses;
                        }

                        if(availableLicenses > 0 && usedLicenses >= availableLicenses){
                            for (let i = 0; i < checkboxes.length; i++){
                                checkboxes[i].classList.add("disable");
                            }
                        } else {
                            for (let i = 0; i < checkboxes.length; i++){
                                checkboxes[i].classList.remove("disable");
                            }
                        }
                    }
                </script>
            </div>';
        } else {
            $html = '<div class="alert alert-warning">No data found</div>';
        }
    }

    // For manage EAS Licenses
    if ($tab == 'manageEasLicenses') {

        $userData = $helper->accountsListSearch('users');
        $domainSettings = $helper->sysadmin_getDomainSettings();

        $users = [];
        if (!empty($userData['responseData'])) {
            foreach ($userData['responseData'] as $acc) {
                $users[] = $acc;
            }
        }

        if (!empty($users)) {
            $html = '
            <div>
                <h1 style="padding: 10px 50px;"> Manage EAS Users </h1>
                <form method="post" action="">
                    <div style="padding: 10px 50px;">

                        <input type="hidden" name="id" value="' . (int)$id . '" />
                        <input type="hidden" name="modop" value="custom" />
                        <input type="hidden" name="formAction" value="saveeaslicensechanges"/>
                        <input class="btn btn-primary" id="save-changes" type="submit" value="Save Changes" disabled />

                        <br>
                        EAS Licenses: <span id="license-count-used">0</span>/<span id="license-count-total">0</span>
                        <br><br><br>
                        <div>
                            <button class="btn btn-link" id="check-all" type="button">Check All</button>
                            <button class="btn btn-link" id="uncheck-all" type="button">Uncheck All</button>
                        </div>
                        <ul class="list-group" style="max-height: 400px;overflow-y: auto;user-select: none;">
            ';

            foreach ($users as $value) {
                $checked = !empty($value['isEasEnabled']) ? 'checked' : '';
                $html .= '
                    <li class="list-group-item check-container">
                        <input class="form-check-input me-1" type="checkbox" name="licenseuser[]" value="' . htmlspecialchars($value['userName']) . '" ' . $checked . '>
                        ' . htmlspecialchars($value['userName']) . '
                    </li>';
            }

            $html .= '
                        </ul>
                    </div>
                </form>
                <script type="text/javascript">
                    let saveChangesBtn = document.getElementById("save-changes");
                    let checkAllBtn = document.getElementById("check-all");
                    let uncheckAllBtn = document.getElementById("uncheck-all");
                    let checkboxes = document.querySelectorAll(\'input[type="checkbox"][name="licenseuser[]"]\');
                    let licenseCountUsed = document.getElementById("license-count-used");
                    let licenseCountTotal = document.getElementById("license-count-total");
                    let availableLicenses = ' . (int)$domainSettings["data"]["domainSettings"]["maxActiveSyncAccounts"] . ';
                    let usedLicenses = 0;

                    function updateCheckedCount(first){
                        if(!first) saveChangesBtn.disabled = false;
                        usedLicenses = 0;
                        checkboxes.forEach(cb => { if(cb.checked) usedLicenses++; });
                        licenseCountUsed.innerText = usedLicenses;
                        licenseCountTotal.innerText = availableLicenses === 0 ? "∞" : availableLicenses;

                        if(availableLicenses > 0 && usedLicenses >= availableLicenses){
                            checkboxes.forEach(cb => cb.classList.add("disable"));
                        } else {
                            checkboxes.forEach(cb => cb.classList.remove("disable"));
                        }
                    }

                    checkAllBtn.onclick = function(e){
                        e.preventDefault();
                        let checkedCount = parseInt(licenseCountUsed.innerText);
                        checkboxes.forEach(cb => {
                            if(!cb.checked && (licenseCountTotal.innerText === "∞" || checkedCount < licenseCountTotal.innerText)){
                                cb.checked = true;
                                checkedCount++;
                            }
                        });
                        updateCheckedCount();
                    };

                    uncheckAllBtn.onclick = function(e){
                        e.preventDefault();
                        checkboxes.forEach(cb => cb.checked = false);
                        updateCheckedCount();
                    };

                    checkboxes.forEach(cb => {
                        cb.onclick = function(){
                            if(cb.classList.contains("disable")) return false;
                            updateCheckedCount();
                        };
                    });

                    updateCheckedCount(true);
                </script>
            </div>';
        } else {
            $html = '<div class="alert alert-warning">No data found</div>';
        }
    }

    // For manage Mailing Lists
    if ($tab == 'manageMailingLists') {
        $mailingLists = $helper->getMailingLists();

        if (!empty($mailingLists) && is_array($mailingLists)) {
            $html .= '<h3 style="margin-top:15px;">Mailing Lists</h3>';

            foreach ($mailingLists as $index => $mailer) {
                if (!is_array($mailer)) continue;

                $mailHeading = !empty($mailer['listAddress']) ? $mailer['listAddress'] : 'Unnamed Mailing List';

                // Format mailing list data
                $formattedData = [];
                foreach ($mailer as $label => $value) {
                    if (is_array($value) || is_object($value)) continue;
                    $formattedData[$helper->labelFormat($label)] = $value;
                }

                $html .= '
                <div class="card mb-3 mailinglist-card" style="border:1px solid #ddd; border-radius:6px;">
                    <div class="card-header" style="background:#f8f9fa; font-weight:bold;">
                        ' . htmlspecialchars($mailHeading) . '
                        <div style="float:right;">
                            <button class="btn custom-btn view-mail" data-target="mailPopup' . $index . '">View</button>
                        </div>
                    </div>
                </div>

                <!-- View Popup -->
                <div id="mailPopup' . $index . '" class="custom-popup">
                    <div class="custom-popup-content">
                        <div class="custom-popup-header">
                            ' . htmlspecialchars($mailHeading) . ' - Details
                            <span class="close-popup">&times;</span>
                        </div>
                        <div class="custom-popup-body">';

                foreach ($formattedData as $label => $value) {
                    if (is_null($value) || trim((string)$value) === '') continue;

                    if ($value === true) {
                        $displayValue = '<i class="fa fa-check" style="color: #02af02"></i>';
                    } elseif ($value === false) {
                        $displayValue = '<i class="fa fa-times" style="color: red"></i>';
                    } else {
                        $displayValue = htmlspecialchars((string)$value);
                    }

                    $html .= '
                            <div class="row mb-2">
                                <div class="col-sm-5 text-left"><strong>' . htmlspecialchars($label) . '</strong></div>
                                <div class="col-sm-7 text-left">' . $displayValue . '</div>
                            </div>';
                }

                $html .= '
                        </div>
                    </div>
                </div>';
            }
        } else {
            $html .= '<div class="alert alert-warning">No mailing lists found</div>';
        }
    }


    // domain aliases
    if ($tab == 'manageDomainAliases') {

        $aliases = $helper->getdomainAliases();

        $domainAliases = [];
        if (!empty($aliases['data']['domainAliasData'])) {
            foreach ($aliases['data']['domainAliasData'] as $data) {
                $domainAliases[] = $data;
            }
        }

        if (!empty($domainAliases)) {
            $html = '-';
        } else {
            $html = '<div class="alert alert-warning">No data found</div>';
        }
    }


    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
}

http_response_code(400);
echo "Invalid request";
exit;