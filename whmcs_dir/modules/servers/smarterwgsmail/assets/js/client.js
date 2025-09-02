$(document).ready(function () {

    // Load Head Tabs Data
    function loadMainTab(tabEl) {
        $(".custom-nav-link").removeClass("active");
        tabEl.addClass("active");

        let tabId = tabEl.attr("href");
        let serviceId = $("#custom-tabs-container").data("serviceid");

        if (tabEl.attr("id") === "logInToWebmail") {
            if (tabId) {
                window.open(tabId, "_blank");
            }
            return;
        }

        $("#custom-loader").show();
        $("#custom-tab-response").hide();
        $(".custom-nav-tabs, .custom-sub-nav").addClass("tabs-disabled"); // disable tabs

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
                    let firstSubTab = $(".custom-sub-link").first();
                    if (firstSubTab.length) {
                        loadSubTab(firstSubTab);
                    }
                } else {
                    $(".custom-management-tabs").hide();
                    $("#custom-tab-response").html(response).show();
                }
            },
            error: function () {
                $("#custom-loader").hide();
                $("#custom-tab-response").html("<p class='text-danger'>Error loading tab.</p>").show();
            },
            complete: function () {
                $(".custom-nav-tabs, .custom-sub-nav").removeClass("tabs-disabled");
            }
        });
    }

    // Load Sub Tabs
    function loadSubTab(tabEl) {
        $(".custom-sub-link").removeClass("active");
        tabEl.addClass("active");

        let subTabId = tabEl.attr("href");
        let serviceId = $("#custom-tabs-container").data("serviceid");

        $("#custom-sub-loader").show();
        $("#custom-sub-response").hide();
        $(".custom-nav-tabs, .custom-sub-nav").addClass("tabs-disabled");

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
            },
            complete: function () {
                $(".custom-nav-tabs, .custom-sub-nav").removeClass("tabs-disabled");
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



    // open popup (view, delete, edit, alias)
    $(document).on("click", ".view-user, .delete-user, .view-alias, .delete-alias, .edit-user, .edit-alias, .view-mail, .edit-mail, .delete-mail", function() {
        let target = $(this).data("target");
        $("#" + target).fadeIn();
    });

    // click outside popup closes it
    $(document).on("click", ".custom-popup", function(e) {
        if ($(e.target).is(".custom-popup")) {
            $(this).fadeOut();
        }
    });

    // close popup (X button or custom close button)
    $(document).on("click", ".close-popup", function() {
        $(this).closest(".custom-popup").fadeOut();
    });

    // confirm delete
    $(document).on("click", ".confirm-delete", function() {
        let serviceId = $("#custom-tabs-container").data("serviceid");

        let $btn = $(this);
        let username = $btn.data("username");
        let type = $btn.data("type");
        let $card = $btn.closest(".custom-popup").siblings(".card");

        $.ajax({
            url: "../modules/servers/smarterwgsmail/lib/managementForm/ajax.php",
            method: "POST",
            data: {
                action: 'managementFormHandling',
                formAction: type === "user" ? "domainUserDelete" : "domainAliasDelete",
                userName: username,
                serviceid: serviceId
            },
            beforeSend: function() {
                $btn.prop("disabled", true).text("Deleting...");
                $("#custom-sub-formresponse").html("").hide(); 
            },
            success: function(response) {
                $card.fadeOut(300, function(){ $(this).remove(); });
                $("#custom-sub-formresponse").html(response).show();
                $btn.closest(".custom-popup").fadeOut();
                let firstSubTab = $(".custom-sub-link.active");
                if (firstSubTab.length) {
                    loadSubTab(firstSubTab);
                }
            },
            error: function(xhr) {
                let errorMsg = '<div class="alert alert-danger">Error deleting ' + type + 
                            ': ' + (xhr.responseText || 'Something went wrong') + '</div>';
                $("#custom-sub-formresponse").html(errorMsg).show();
                $btn.closest("#custom-sub-response").hide();
            },
            complete: function() {
                $btn.prop("disabled", false).text("Yes, Delete");
            }
        });
    });

    // confirm edit 
    $(document).on("click", ".edit-domain-user, .edit-domain-alias", function(e) {
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
                $btn.closest(".custom-popup").fadeOut();
                $form[0].reset();
                let firstSubTab = $(".custom-sub-link.active");
                if (firstSubTab.length) {
                    loadSubTab(firstSubTab);
                }
            },
            error: function () {
                $btn.val(originalBtnText).prop("disabled", false);
                $("#custom-sub-formresponse").html("<p class='text-danger'>Error Submitting the form.</p>").show();
            }
        });
    });
    


});
