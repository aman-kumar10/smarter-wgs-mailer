<link href="{$assets_link}css/client.css" rel="stylesheet">
<script src="{$assets_link}js/client.js"></script>

<div class="custom-clientarea" id="custom-tabs-container" data-serviceid="{$serviceid}">

    {* Main Tabs *}
    <ul class="custom-nav-tabs">
        <li class="custom-nav-item">
            <a href="hostingInfo" id="hostingInfo" class="custom-nav-link active"><i class="fas fa-globe fa-fw"></i> Hosting Information</a>
        </li>
        <li class="custom-nav-item">
            <a href="licenseInfo" id="licenseInfo" class="custom-nav-link"><i class="fas fa-key fa-fw"></i> License Information</a>
        </li>
        <li class="custom-nav-item">
            <a href="domainSettings" id="domainSettings" class="custom-nav-link"><i class="fas fa-cog fa-fw"></i> Domain Settings</a>
        </li>

        {if $managements}
        <li class="custom-nav-item">
            <a href="managementsTab" id="managementsTab" data-serviceid="{$serviceid}" class="custom-nav-link"><i class="fas fa-tasks fa-fw"></i> Managements</a>
        </li>
        {/if}
    </ul>

    {* Loader *}
    <div class="custom-tab-content">
        <div id="custom-loader" class="custom-loader" style="display:none;">Loading...</div>
        <div id="custom-tab-response" class="custom-tab-response"></div>
    </div>

    {* Management Sub Tabs *}
    {if $managements}
    <div class="custom-management-tabs" style="display:none;">
        <ul class="custom-sub-nav">
            {foreach $managements as $management name=mgmtLoop}
                <li class="custom-sub-item">
                    <a href="{$management.attID}" id="{$management.attID}"
                    class="custom-sub-link {if $smarty.foreach.mgmtLoop.first}active{/if}">
                    <i class="fas {$management.attFaCls} fa-fw"></i> {$management.tabname}
                    </a>
                </li>
            {/foreach}
        </ul>
        <div class="custom-sub-tab-content">
            <div id="custom-sub-loader" class="custom-loader" style="display:none;">Loading...</div>
            <div id="custom-sub-formresponse" class="custom-tab-formresponse"></div>
            <div id="custom-sub-response" class="custom-tab-response"></div>
        </div>
    </div>
    {/if}

</div>
