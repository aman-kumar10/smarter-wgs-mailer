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

    // Get Users
    if ($tab == 'userAccount') {

        $userData = $helper->accountsListSearch('users');

        if (!empty($userData['responseData']) && is_array($userData['responseData'])) {
            $html .= '<h3 style="margin-top:15px;">Users Management</h3>
            <div class="add-frm-btn">
                <a href="addUser" id="addUser" class="add-user">
                    <i class="fas fa-user-plus fa-fw"></i> Add User
                </a>
            </div>
            <div class="tb-data-container">
                <div class="row border-elememt management-data-headings">
                    <div class="col-sm-8 text-center" style="font-weight: 600;">
                        Users
                    </div>
                    <div class="col-sm-4 text-center"  style="font-weight: 600;">
                        Actions
                    </div>
                </div>';

            foreach ($userData['responseData'] as $index => $user) {
                if (!is_array($user)) continue;

                $userHeading = !empty($user['displayName']) ? $user['displayName'] : 'Unnamed User';

                $formattedData = [];
                foreach ($user as $label => $value) {
                    if (is_array($value) || is_object($value)) continue;
                    $formattedData[$helper->labelFormat($label)] = $value;
                }

                $html .= '
                <div class="row border-elememt">
                    <div class="col-sm-8 text-center">
                        ' . htmlspecialchars($userHeading) . '
                    </div>
                    <div class="col-sm-4 text-center action-btns">
                        <i class="fas fa-eye fa-fw view-user" style="background: transparent; color: #007bff !important;" data-target="userPopup' . $index . '"></i>
                        <i class="fas fa-edit fa-fw edit-user" style="background: transparent; color: green !important;" data-editdata="'.$user['userName'].'"></i>
                        <i class="fas fa-trash fa-fw delete-user" style="background: transparent; color: red !important;" data-target="deletePopup' . $index . '"></i>
                    </div>
                </div>

                <!--  View Popup  -->
                <div id="userPopup' . $index . '" class="custom-popup">
                    <div class="custom-popup-content">
                        <div class="custom-popup-header">
                            ' . htmlspecialchars($userHeading) . ' - Details
                            <span class="close-popup">&times;</span>
                        </div>
                        <div class="custom-popup-body">
                            <table id="userDetailTbl' . $index . '" class="data-tbl">
                                <tr class="management-data-headings">
                                    <th>Field</th>
                                    <th>Value</th>
                                </tr>';

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
                            <tr>
                                <td>' . htmlspecialchars($label) . '</div>
                                <td>' . $displayValue . '</div>
                            </tr>';
                }

                $html .= '</table>
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

            $html .= '</div>';

        } else {
            $html .= '<div class="alert alert-warning">No users found</div>';
        }
    }

    // Get Aliases
    if ($tab == 'userAliases') {
        $aliasData  = $helper->accountsListSearch('aliases');

        if (!empty($aliasData['responseData']) && is_array($aliasData['responseData'])) {
            $html .= '<h3 style="margin-top:30px;">Aliases Management</h3>
            <div class="add-frm-btn">
                <a href="addAlias" id="addAlias" class="add-alias">
                    <i class="fas fa-plus-circle fa-fw"></i> Add Alias
                </a>
            </div>
            <div class="tb-data-container">
                <div class="row management-data-headings border-elememt">
                    <div class="col-sm-8 text-center" style="font-weight: 600;">
                        Aliases
                    </div>
                    <div class="col-sm-4 text-center"  style="font-weight: 600;">
                        Actions
                    </div>
                </div>';

            foreach ($aliasData['responseData'] as $aIndex => $alias) {
                if (!is_array($alias)) continue;

                $aliasHeading = !empty($alias['displayName']) ? $alias['displayName'] : 'Unnamed Alias';


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
                <div class="row border-elememt">
                    <div class="col-sm-8 text-center">
                        ' . htmlspecialchars($aliasHeading) . '
                    </div>
                    <div class="col-sm-4 text-center action-btns">
                        <i class="fas fa-eye fa-fw view-alias" style="background: transparent; color: #007bff !important;" data-target="aliasPopup' . $aIndex . '"></i>
                        <i class="fas fa-edit fa-fw edit-alias" style="background: transparent; color: green !important;" data-editdata="'.$alias['userName'].'"></i>
                        <i class="fas fa-trash fa-fw delete-alias" style="background: transparent; color: red !important;" data-target="aliasDeletePopup' . $aIndex . '"></i>
                    </div>
                </div>

                <!-- view popup -->
                <div id="aliasPopup' . $aIndex . '" class="custom-popup">
                    <div class="custom-popup-content">
                        <div class="custom-popup-header">
                            ' . htmlspecialchars($aliasHeading) . ' - Details
                            <span class="close-popup">&times;</span>
                        </div>
                        <div class="custom-popup-body">
                        <table id="userDetailTbl' . $index . '" class="data-tbl">
                                <tr class="management-data-headings">
                                    <th>Field</th>
                                    <th>Value</th>
                                </tr>';

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
                            <tr>
                                <td>' . htmlspecialchars($label) . '</div>
                                <td>' . $displayValue . '</div>
                            </tr>';
                }

                $html .= '</table>
                        </div>
                    </div>
                </div>

                <!-- delete confirm popup -->
                <div id="aliasDeletePopup' . $aIndex . '" class="custom-popup">
                    <div class="custom-popup-content">
                        <span class="close-popup">&times;</span>
                        <h1 style="margin: 0;"><i class="fas fa-times-circle fa-fw" style="color: red;"></i></h1>
                        <p>Are you sure you want to delete <strong>' . htmlspecialchars($alias['userName']) . '</strong>?</p>
                        <button class="btn custom-btn confirm-delete" 
                                data-username="' . htmlspecialchars($alias['userName']) . '" 
                                data-type="alias">Yes, Delete</button>
                    </div>
                </div>';
            }

            $html .= '</div>';

        } else {
            $html .= '<div class="alert alert-warning">No aliases found</div>';
        }
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
                        <input type="hidden" name="formAction" value="savemapilicensechanges"/>
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
                        <input class="btn btn-primary save-easLicenses" id="save-easLicenses" type="submit" value="Save Changes" disabled />

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
            $html .= '<h3 style="margin-top:30px;">Mailing Lists Management</h3>
            <div class="add-frm-btn">
                <a href="addMail" id="addMail" class="add-mail">
                    <i class="fas fa-plus-circle fa-fw"></i> Add Mail List
                </a>
            </div>
            <div class="tb-data-container">
                <div class="row management-data-headings border-elememt">
                    <div class="col-sm-8 text-center" style="font-weight: 600;">
                        Mailing Lists
                    </div>
                    <div class="col-sm-4 text-center"  style="font-weight: 600;">
                        Actions
                    </div>
                </div>';

            foreach ($mailingLists as $index => $mailer) {
                if (!is_array($mailer)) continue;

                $mailHeading = !empty($mailer['listAddress']) ? $mailer['listAddress'] : 'Unnamed Mailing List';

                // Format mailing list data
                $formattedData = [];
                foreach ($mailer as $label => $value) {
                    if (is_array($value) || is_object($value)) continue;
                    $formattedData[$helper->labelFormat($label)] = $value;
                }

                // $edit-btn = '<i class="fas fa-edit fa-fw edit-mail-login" style="background: transparent; color: green !important;" data-editdata="' . htmlspecialchars($mailHeading) . '"></i>';
                
                $html .= '
                <div class="row border-elememt">
                    <div class="col-sm-8 text-center">
                        ' . htmlspecialchars($mailHeading) . '
                    </div>
                    <div class="col-sm-4 text-center action-btns">
                        <i class="fas fa-eye fa-fw view-mail" style="background: transparent; color: #007bff !important;" data-target="mailPopup' . $index . '"></i>
                        
                        <i class="fas fa-trash fa-fw delete-mail" style="background: transparent; color: red !important;" data-target="mailDeletePopup' . $index . '"></i>
                    </div>
                </div>

                <!-- View Popup -->
                <div id="mailPopup' . $index . '" class="custom-popup">
                    <div class="custom-popup-content">
                        <div class="custom-popup-header">
                            ' . htmlspecialchars($mailHeading) . ' - Details
                            <span class="close-popup">&times;</span>
                        </div>
                        <div class="custom-popup-body">
                            <table id="userDetailTbl' . $index . '" class="data-tbl">
                                    <tr class="management-data-headings">
                                        <th>Field</th>
                                        <th>Value</th>
                                    </tr>';

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
                            <tr>
                                <td>' . htmlspecialchars($label) . '</div>
                                <td>' . $displayValue . '</div>
                            </tr>';
                }

                $html .= '</table>
                        </div>
                    </div>
                </div>
                
                <!-- delete confirm popup -->
                <div id="mailDeletePopup' . $index . '" class="custom-popup">
                    <div class="custom-popup-content">
                        <span class="close-popup">&times;</span>
                        <h1 style="margin: 0;"><i class="fas fa-times-circle fa-fw" style="color: red;"></i></h1>
                        <p>Are you sure you want to delete <strong>' . htmlspecialchars($mailHeading) . '</strong>?</p>
                        <button class="btn custom-btn confirm-delete" 
"                                data-username="' . $mailer['id'] . '" 
                                data-type="mail">Yes, Delete</button>
                    </div>
                </div>';
            }
            $html .= '</div>';
        } else {
            $html .= '<div class="alert alert-warning">No mailing lists found</div>';
        }
    }


    // domain aliases
    if ($tab == 'manageDomainAliases') {

        $aliases = $helper->getdomainAliases();

        if (is_array($aliases) && !empty($aliases)) {

            $options = '';
            foreach($aliases as $alias) {
                $options .= '<option value="'.$alias['name'].'">'.$alias['name'].'</option>';
            }

            $html = '<div>
            <h1 style="padding: 10px 50px;"> Manage Domain Aliases </h1>
            <div style="padding: 10px 50px;">
                <table>
                    <tr>
                        <td>
                            <div style="float: left;">
                            <form method="post" action="clientarea.php?action=productdetails">
                                <input type="hidden" name="id" value="{$id}" />
                                <input type="hidden" name="modop" value="custom" />
                                <input type="hidden" name="a" value="adddomainaliaspage" />
                                <input type="submit" value="Add Domain Alias" />
                            </form>
                            </div>
                            <div style="float: left; padding-left: 15px;">
                                <form method="post" action="clientarea.php?action=productdetails">
                                    <input type="hidden" name="id" value="{$id}" />
                                    <input type="hidden" name="modop" value="custom" />
                                    <input type="hidden" name="a" value="removedomainalias" />
                                    <input type="submit" value="Remove Domain Alias" />

                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <select style="width: 600px;" size="10" class="form-control" name="selectedalias" required>
                                '.$options.'
                            </select>
                        </td>
                    </tr>
                    </form>
                </table>
                </div>
            </div>
            ';
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