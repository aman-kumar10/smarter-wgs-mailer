$(document).ready(function () {

    // Load Head Tabs Data
    function loadMainTab(tabEl) {
        $(".custom-nav-link").removeClass("active");
        tabEl.addClass("active");

        let tabId = tabEl.attr("href");
        let serviceId = $("#custom-tabs-container").data("serviceid");

        $("#custom-loader").show();
        $("#custom-tab-response").hide();

        $.ajax({
            url: '../modules/servers/smarterwgsmail/lib/headTabs/ajax.php',
            method: "POST",
            data: {
                action: "headTabRequest",
                tab: tabId,
                serviceid: serviceId
            },
            success: function (response) {
                $("#custom-loader").hide();

                if (tabId === "managementsTab") {
                    $(".custom-management-tabs").show();

                    // auto-load first sub-tab
                    let firstSubTab = $(".custom-sub-link").first();
                    if (firstSubTab.length) {
                        loadSubTab(firstSubTab); // call sub loader directly
                    }

                } else {
                    $(".custom-management-tabs").hide();
                    $("#custom-tab-response").html(response).show();
                }
            },
            error: function () {
                $("#custom-loader").hide();
                $("#custom-tab-response").html("<p class='text-danger'>Error loading tab.</p>").show();
            }
        });
    }

    // Load Management Tabs Data
    function loadSubTab(tabEl) {
        $(".custom-sub-link").removeClass("active");
        tabEl.addClass("active");

        let subTabId = tabEl.attr("href");
        let serviceId = $("#custom-tabs-container").data("serviceid");


        $("#custom-sub-loader").show();
        $("#custom-sub-response").hide();

        $.ajax({
            url: '../modules/servers/smarterwgsmail/lib/managementTabs/ajax.php',
            method: "POST",
            data: {
                action: "managementTabRequest",
                tab: subTabId,
                serviceid: serviceId
            },
            success: function (response) {
                $("#custom-sub-loader").hide();
                $("#custom-sub-response").html(response).show();
            },
            error: function () {
                $("#custom-sub-loader").hide();
                $("#custom-sub-response").html("<p class='text-danger'>Error loading sub-tab.</p>").show();
            }
        });
    }

    // Auto load first main tab
    let firstMainTab = $(".custom-nav-link").first();
    if (firstMainTab.length) {
        loadMainTab(firstMainTab);
    }

    // Head Tabs
    $(document).on("click", ".custom-nav-link", function (e) {
        e.preventDefault();
        loadMainTab($(this));
    });

    // Management Tabs
    $(document).on("click", ".custom-sub-link", function (e) {
        e.preventDefault();
        loadSubTab($(this));
        $("#custom-sub-formresponse").hide();
    });


    // Add SmarterMail User
    $(document).on("click", ".mgmt-form-btn", function (e) {
        e.preventDefault();

        let serviceId = $("#custom-tabs-container").data("serviceid");
        let $form = $(this).closest("form");
        let $btn = $form.find("input[type=submit]");
        let originalBtnText = $btn.val();

        $.ajax({
            url: '../modules/servers/smarterwgsmail/lib/managementForm/ajax.php',
            method: "POST",
            data: $form.serialize() + "&action=managementFormHandling&serviceid=" + serviceId,
            beforeSend: function () {
                $btn.val("Submitting...").prop("disabled", true);
                $btn.css('color', '#000');
            },
            success: function (response) {
                $("#custom-sub-formresponse").html(response).show();
                $btn.val(originalBtnText).prop("disabled", false);
                $form[0].reset();
            },
            error: function () {
                $btn.val(originalBtnText).prop("disabled", false);
                $("#custom-sub-formresponse").html("<p class='text-danger'>Error Submitting the form.</p>").show();
            }
        });
    });


});
