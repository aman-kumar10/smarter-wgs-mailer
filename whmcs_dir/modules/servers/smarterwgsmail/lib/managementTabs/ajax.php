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


    // Display Users
    if($tab == 'userAccount') {
        $userData = $helper->accountsListSearch('users');
    }

    // Display Aliases
    if($tab == 'userAliases') {
        $userData = $helper->accountsListSearch('aliases');
    }

    if (is_array($userData['responseData'][0])) {
        if (!empty($userData['responseData'][0])) {
            $formattedData = [];
            foreach ($userData['responseData'][0] as $label => $value) {
                $formattedData[$helper->labelFormat($label)] = $value;
            }

            $html = '';
            foreach ($formattedData as $label => $value) {
                $displayValue = (is_null($value) || trim((string) $value) === '') ? '-' : $value;
                $html .= '
                    <div class="row mb-2">
                        <div class="col-sm-5 text-left">
                            <strong>' . htmlspecialchars($label) . '</strong>
                        </div>
                        <div class="col-sm-7 text-left">
                            ' . htmlspecialchars((string) $displayValue) . '
                        </div>
                    </div>
                ';
            }
        } else {
            $html = '<div class="alert alert-warning">No data found</div>';
        }
    } elseif (is_string($userData['responseData']) && !empty($userData['responseData'])) {
        $html = '<div class="alert alert-info">' . htmlspecialchars($userData['responseData']) . '</div>';
    } else {
        $html = '<div class="alert alert-warning">No data found</div>';
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
                        <input id="btn-submit" style="position: relative;" class="btn btn-primary mgmt-form-btn" type="submit" value="Add User" disabled />
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
                        <textarea cols="36" rows="15" name="newaliasemailaddress" style="overflow: auto; resize: none; width: 100%"></textarea>
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
                    <input class="btn btn-primary mgmt-form-btn" style="position: relative;" type="submit" value="Add Alias" />
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
                        <input type="hidden" name="a" value="saveeaslicensechanges"/>
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

        $list = [];
        if (!empty($mailingLists['data']['items'])) {
            foreach ($mailingLists['data']['items'] as $acc) {
                $list[] = $acc;
            }
        }

        $vars = [
            'mailingLists' => $list,
        ];

        if (!empty($list)) {
            $html = '
            <form method="post" action="">
                <input type="hidden" name="id" value="{$id}" />
                <input class="btn btn-link" type="submit" value="Go Back" />
            </form>
            
            <h1 style="padding: 0px 50px;"> Manage Mailing Lists </h1>
            <div style="padding: 10px 50px;">
                <div style="box-shadow: 2px 2px 8px #9b9b9b; border-radius: 3px;">
                    
                    <div class="sm-top-row">
                        <input type="text" onkeyup="searchKeyPressed(event)" 
                            style="margin: 5px; grid-column: 1 / 4; grid-row: 1; text-indent:27px; width: 50%;" />
                        <div class="sm-search-icon" style="grid-column: 1 / 4; grid-row: 1;">🔍</div>
                        
                        <form method="post" action="" style="grid-column: 4 / 4; float: right;">
                            <input type="hidden" name="id" value="{$id}" />
                            <input type="hidden" name="modop" value="custom" />
                            <input type="hidden" name="a" value="addmailinglistpage" />
                            <input class="btn btn-primary" type="submit" value="Add Mailing List" style="float: right; margin: 5px;" />
                        </form>
                    </div>

                    <div class="sm-header-row">
                        <div style="grid-column: 1 / 3; grid-row: 1; padding: 8px;">
                            Username
                        </div>
                        <div style="grid-column: 2 / 3; grid-row: 1; padding: 8px;">
                            Subscriber Count
                        </div>
                        <div style="grid-column: 3 / 3; grid-row: 1; padding: 8px;">
                            Actions
                        </div>
                    </div>

                    <div class="sm-row-container">
                        {foreach from=$mailingLists item=value}
                            <div class="sm-grid-row" username="{$value.userName}">
                                <div class="sm-grid-cell" style="grid-column: 1 / 3;">
                                    {$value.listAddress}
                                </div>
                                <div class="sm-grid-cell" style="grid-column: 2 / 3;">
                                    {$value.listSubscriberCount}
                                </div>
                                <div class="sm-grid-cell-action" style="grid-column: 3 / 3;">
                                    <form method="post" action="" style="float:right;">
                                        <input type="hidden" name="id" value="{$id}" />
                                        <input type="hidden" name="modop" value="custom" />
                                        <input type="hidden" name="a" value="removemailinglist" />
                                        <input type="hidden" name="mlid" value="{$value.id}" />
                                        <input type="submit" value="🗑️" class="sm-unbutton" />
                                    </form>
                                    <form method="post" action="" style="float:right;">
                                        <input type="hidden" name="id" value="{$id}" />
                                        <input type="hidden" name="modop" value="custom" />
                                        <input type="hidden" name="a" value="editmailinglistpage" />
                                        <input type="hidden" name="mlid" value="{$value.id}" />
                                        <input type="submit" value="📝" class="sm-unbutton" />
                                    </form>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                </div>
            </div>

            <script type="text/javascript">
                function searchKeyPressed(event){
                    var inputStr = event.target.value.toLowerCase();
                    var elems = document.getElementsByClassName("sm-grid-row");
                    for(let i = 0; i < elems.length; i++){
                        var elemUsername = elems[i].getAttribute("username");
                        if(elemUsername && elemUsername.toLowerCase().indexOf(inputStr) > -1 || inputStr.length === 0){
                            elems[i].style.display = "";
                        } else {
                            elems[i].style.display = "none";
                        }
                    }
                }
            </script>
            ';
        } else {
            $html = '<div class="alert alert-warning">No data found</div>';
        }
    }

    // domain aliases
    if ($tab == 'manageDomainAliases') {

        $aliases = $helper->getMailingLists();

        $domainAliases = [];
        if (!empty($aliases['data']['domainAliasData'])) {
            foreach ($aliases['data']['domainAliasData'] as $data) {
                $domainAliases[] = $data;
            }
        }

        $vars = [
            'domainAliases' => $domainAliases, 
        ];

        if (!empty($domainAliases)) {
            $html = '
            <div>
                <h1 style="padding: 10px 50px;"> Manage Domain Aliases </h1>
                <div style="padding: 10px 50px;">
                    <table>
                        <tr>
                            <td>
                                <div style="float: left;">
                                    <form method="post" action="">
                                        <input type="hidden" name="id" value="{$id}" />
                                        <input type="hidden" name="modop" value="custom" />
                                        <input type="hidden" name="a" value="adddomainaliaspage" />
                                        <input type="submit" value="Add Domain Alias" />
                                    </form>
                                </div>
                                <div style="float: left; padding-left: 15px;">
                                    <form method="post" action="">
                                        <input type="hidden" name="id" value="{$id}" />
                                        <input type="hidden" name="modop" value="custom" />
                                        <input type="hidden" name="a" value="removedomainalias" />
                                        <select style="width: 300px;" size="1" name="selectedalias" required>
                                            {foreach from=$domainAliases item=value}
                                                <option value="{$value.name}">{$value.name}</option>
                                            {/foreach}
                                        </select>
                                        <input type="submit" value="Remove Domain Alias" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>';
        } else {
            $html = '<div class="alert alert-warning">No data found</div>';
        }
    }

    // For Form Values
    if($tab == 'logInToWebmail') {

        $domainSettings = $helper->sysadmin_getDomainSettings();

        $loginUrl = $helper->getWebmailLoginURL(!empty($domainSettings['mainDomainAdmin']) ? $domainSettings['mainDomainAdmin'] : '');

        if(!empty($loginUrl)) {
            $html = '<div class="alert alert-success"><a target="_blank" href="'.$loginUrl.'" class="btn btn-primary">Login</a></div>';
        } else {
            $html = '<div class="alert alert-warning">Unable to generate URL</div>';
        }


    }




    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
}

http_response_code(400);
echo "Invalid request";
exit;