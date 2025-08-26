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
                    <div class="tab-pane fade
                                {if $activePage && $activePage eq $management.attID}
                                    show active
                                {elseif !$activePage && $management@first}
                                    show active
                                {/if}" 
                        role="tabpanel" 
                        id="{$management.attID}">

                        {if isset($management.response)}
                            {if is_array($management.response) && $management.response|@count > 0}
                                {foreach $management.response as $resp}
                                    {foreach from=$resp key=label item=value}
                                        <div class="row">
                                            <div class="col-sm-5 text-left"><strong>{$label}</strong></div>
                                            <div class="col-sm-7 text-left">
                                                {if $value|trim == ''}-{else}{$value}{/if}
                                            </div>
                                        </div>
                                    {/foreach}
                                {/foreach}
                            {elseif $management.response != ''}
                                <div class="alert alert-danger">{$management.response}</div>
                            {else}
                                <div class="alert alert-warning">No data found.</div>
                            {/if}
                        {else}
                            <div class="alert alert-warning">No data found.</div>
                        {/if}

                    </div>
                {/foreach}
            </div>
        </div>
        {/if}

        

    </div>

</div>
{/if}