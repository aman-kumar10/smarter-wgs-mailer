<link href="{$assets_link}css/client.css" rel="stylesheet">
<script src="{$assets_link}js/client.js"></script>

{if isset($varsRes['noResponse'])}
    <div class="alert alert-warning">{$varsRes['noResponse']}</div>
{else}
<div class="clientarea-output">

    <ul class="nav nav-tabs responsive-tabs-sm">
        <li class="nav-item">
            <a href="#domainHosting" data-toggle="tab" class="nav-link active"><i class="fas fa-globe fa-fw"></i> Hosting Information</a>
        </li>
        <li class="nav-item">
            <a href="#domainLicense" data-toggle="tab" class="nav-link"><i class="fas fa-key fa-fw"></i> License Information</a>
        </li>
        <li class="nav-item">
            <a href="#domainSettings" data-toggle="tab" class="nav-link"><i class="fas fa-cog fa-fw"></i> Domain Settings</a>
        </li>

        {if $managements}
            <li class="nav-item">
                <a href="#managementsTab" data-toggle="tab" class="nav-link"><i class="fas fa-tasks fa-fw"></i> Managements</a>
            </li>
        {/if}
    </ul>

    <div class="tab-content">

        {* ---------------- Hosting Information ---------------- *}
        <div class="tab-pane fade show active text-center" role="tabpanel" id="domainHosting">
            {if isset($domainData) && $domainData}
                {foreach from=$domainData key=label item=value}
                    <div class="row">
                        <div class="col-sm-5 text-left"><strong>{$label}</strong></div>
                        <div class="col-sm-7 text-left">{if $value == ''}-{else}{$value}{/if}</div>
                    </div>
                {/foreach}
            {elseif isset($domainDataError)}
                <div class="alert alert-danger">{$domainDataError}</div>
            {else}
                <div class="alert alert-warning">No domain data found.</div>
            {/if}
        </div>

        {* ---------------- License Information ---------------- *}
        <div class="tab-pane fade text-center" role="tabpanel" id="domainLicense">
            {if isset($domainLicense) && $domainLicense}
                {foreach from=$domainLicense key=label item=value}
                    <div class="row">
                        <div class="col-sm-5 text-left"><strong>{$label}</strong></div>
                        <div class="col-sm-7 text-left">{if $value == ''}-{else}{$value}{/if}</div>
                    </div>
                {/foreach}
            {elseif isset($domainLicenseError)}
                <div class="alert alert-danger">{$domainLicenseError}</div>
            {else}
                <div class="alert alert-warning">No license data found.</div>
            {/if}
        </div>

        {* ---------------- Domain Settings ---------------- *}
        <div class="tab-pane fade text-center" role="tabpanel" id="domainSettings">
            {if isset($domainSettings) && $domainSettings}
                {foreach from=$domainSettings key=label item=value}
                    <div class="row">
                        <div class="col-sm-5 text-left"><strong>{$label}</strong></div>
                        <div class="col-sm-7 text-left">{if $value == ''}-{else}{$value}{/if}</div>
                    </div>
                {/foreach}
            {elseif isset($domainSettingsError)}
                <div class="alert alert-danger">{$domainSettingsError}</div>
            {else}
                <div class="alert alert-warning">No domain settings found.</div>
            {/if}
        </div>

        {* ---------------- Managements ---------------- *}
        {if $managements}
        <div class="tab-pane fade text-center" role="tabpanel" id="managementsTab">

            {* <!-- Management sub-tabs --> *}
            <ul class="nav nav-tabs responsive-tabs-sm" id="managementLinks">
                {foreach $managements as $management}
                    <li class="nav-item">
                        {if $activePage && $activePage eq $management.attID}
                            <a href="#{$management.attID}"
                            data-toggle="tab"
                            class="nav-link management-link active">
                                <i class="fas {$management.attFaCls} fa-fw"></i> {$management.tabname}
                            </a>
                        {elseif !$activePage && $management@first}
                            <a href="#{$management.attID}"
                            data-toggle="tab"
                            class="nav-link management-link active">
                                <i class="fas {$management.attFaCls} fa-fw"></i> {$management.tabname}
                            </a>
                        {else}
                            <a href="/clientarea.php?action=productdetails&id={$service_id}&page={$management.attID}"
                            class="nav-link management-link">
                                <i class="fas {$management.attFaCls} fa-fw"></i> {$management.tabname}
                            </a>
                        {/if}
                    </li>
                {/foreach}
            </ul>


            <div class="tab-content mt-3">
                {foreach $managements as $management}
                    <div class="tab-pane fade {if $activePage && $activePage eq $management.attID} show active {elseif !$activePage && $management@first} show active {/if}" role="tabpanel" id="{$management.attID}">
                        <div class="row">
                            <h2>Add SmarterMail User</h2>
                            <form method="post" action="">

                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input class="form-control" type="text" id="username" name="username" style="width: 300px" onkeyup="checkPasswordReqs()">
                                </div>

                                <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <label class="input-group-text" for="authtype">Authentication Mode</label>
                                        </div>
                                        <select class="custom-select" name="authtype" id="authtype">
                                            <option value="0" selected="selected">SmarterMail</option>
                                            <option value="1">Active Directory</option>
                                        </select>
                                </div>

                                <div class="form-group sm-auth">
                                    <label for="password">Password</label>
                                    <input class="form-control" type="text" id="password" name="password" style="width: 300px" onkeyup="checkPasswordReqs()">
                                    <div>
                                        <div>Password Requirements</div>
                                        <div id="req-capital" class="req-hide">Contain one UPPERCASE letter</div>
                                        <div id="req-lower" class="req-hide">Contain one lowercase letter</div>
                                        <div id="req-not-username" class="req-hide">Does not match your username</div>
                                        <div id="req-number" class="req-hide">Contain any number 0 through 9</div>
                                        <div id="req-symbol" class="req-hide">Contain any special character such as #, @, &, etc.</div>
                                        <div id="req-length">Is at least 5 characters long</div>
                                    </div>
                                </div>
                                <div class="form-group sm-auth">
                                    <label for="displayname">Display Name</label>
                                    <input class="form-control" type="text" id="displayname" name="displayname" style="width: 300px">
                                </div>

                                <div class="form-group ad-auth">
                                    <label for="adusername">AD Username</label>
                                    <input class="form-control" type="text" id="adusername" name="adusername" style="width: 300px">
                                </div>

                                <div class="form-group ad-auth">
                                    <label for="addomain">AD Domain</label>
                                    <input class="form-control" type="text" id="addomain" name="addomain" style="width: 300px">
                                </div>

                                <div class="form-group">
                                    <label for="mailboxsize">Mailbox Size in MB (0 = unlimited)</label>
                                    <input class="form-control" type="number" id="mailboxsize" name="mailboxsize" min="0" max="1024" style="width: 150px">
                                </div>

                                <div class="form-group row sm-auth">
                                    <div class="col-sm-10">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="resetonlogincheckbox" name="resetonlogincheckbox">
                                            <label class="form-check-label" for="resetonlogincheckbox">
                                                Reset Password On Login
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-10">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="domainadmincheckbox" name="domainadmincheckbox">
                                            <label class="form-check-label" for="domainadmincheckbox">
                                                Domain Administrator
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <input id="btn-submit" class="btn btn-primary" type="submit" value="Add User" />
                            </form>
                        </div>
                    </div>
                {/foreach}
            </div>
        </div>
        {/if}

        

    </div>

</div>
{/if}