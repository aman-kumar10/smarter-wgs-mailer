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


    // Add User/Alias/Mail-list
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
                $("#ajax-overlay").show();
                $btn.val("Submitting...").prop("disabled", true);
                $btn.css('color', '#000');
            },
            success: function (response) {
                $("#custom-sub-formresponse").html(response).show();
                $btn.val(originalBtnText).prop("disabled", false);
                $form[0].reset();
                let firstSubTab = $(".custom-sub-link.active");
                if (firstSubTab.length) {
                    loadSubTab(firstSubTab);
                }
            },
            error: function () {
                $btn.val(originalBtnText).prop("disabled", false);
                $("#custom-sub-formresponse").html("<p class='text-danger'>Error Submitting the form.</p>").show();
            },
            complete: function () {
                $("#ajax-overlay").hide();
            }
        });
    });



    // open popup to view the user/alias
    $(document).on("click", ".view-user, .view-alias, .view-mail", function() {
        let target = $(this).data("target");

        $("#ajax-overlay").show();

        setTimeout(function() {
            $("#ajax-overlay").hide();
            $("#" + target).fadeIn();
        }, 1000);
        
    });

    // open popup to delete the user/alias
    $(document).on("click", ".delete-user, .delete-alias, .delete-mail", function() {
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

        let formAction = null;

        if(type === 'user') {
            formAction = 'domainUserDelete';
        } else if(type === 'alias') {
            formAction = 'domainAliasDelete';
        } else {
            formAction = 'domainMailDelete';
        }

        $.ajax({
            url: "../modules/servers/smarterwgsmail/lib/managementForm/ajax.php",
            method: "POST",
            data: {
                action: 'managementFormHandling',
                formAction: formAction,
                userName: username,
                serviceid: serviceId
            },
            beforeSend: function() {
                $("#ajax-overlay").show();
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
                $("#ajax-overlay").hide();
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
                $("#ajax-overlay").show();
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
            },
            complete: function () {
                $("#ajax-overlay").hide();
            }
        });
    });

    // edit user/alias popup
    $(document).on("click", ".edit-user, .edit-alias", function(e) {
        e.preventDefault();

        let serviceId = $("#custom-tabs-container").data("serviceid");
        let userName = $(this).data('editdata');
        let $btn = $(this);
        
        let formAction = $btn.hasClass("edit-user") ? "getUserEditPopup" : "getAliasEditPopup";

        $.ajax({
            url: '../modules/servers/smarterwgsmail/lib/managementForm/ajax.php',
            method: "POST",
            data: {
                action: 'managementFormHandling',
                formAction: formAction,
                userName: userName,
                serviceid: serviceId
            },
            beforeSend: function () {
                $("#ajax-overlay").show();
            },
            success: function (response) {
                $("#custom-sub-formresponse").html(response).show();
            },
            error: function () {
                $("#custom-sub-formresponse").html("<p class='text-danger'>An error occurred while getting the edit form.</p>").show();
            },
            complete: function () {
                $("#ajax-overlay").hide();
            }
        });
    });

    // add user/alias popup
    $(document).on("click", ".add-user, .add-alias, .add-mail, .save-easLicenses", function(e) {
        e.preventDefault();

        let serviceId = $("#custom-tabs-container").data("serviceid");
        
        let formAction = $(this).attr('id');

        $.ajax({
            url: '../modules/servers/smarterwgsmail/lib/managementForm/ajax.php',
            method: "POST",
            data: {
                action: 'managementFormHandling',
                formAction: formAction,
                serviceid: serviceId
            },
            beforeSend: function () {
                $("#ajax-overlay").show();
            },
            success: function (response) {
                $("#custom-sub-formresponse").html(response).show();
            },
            error: function () {
                $("#custom-sub-formresponse").html("<p class='text-danger'>An error occurred while getting the edit form.</p>").show();
            },
            complete: function () {
                $("#ajax-overlay").hide();
            }
        });
    });


    // Edit mail list / login url
    // $(document).on("click", ".edit-mail-login", function(e) {
    //     e.preventDefault();

    //     let serviceId = $("#custom-tabs-container").data("serviceid");
        
    //     $.ajax({
    //         url: '../modules/servers/smarterwgsmail/lib/managementForm/ajax.php',
    //         method: "POST",
    //         data: {
    //             action: 'managementFormHandling',
    //             formAction: 'loginToEditMail',
    //             serviceid: serviceId
    //         },
    //         beforeSend: function () {
    //             $("#ajax-overlay").show();
    //         },
    //         success: function (response) {
    //             $("#custom-sub-formresponse").html(response).show();
    //         },
    //         error: function () {
    //             $("#custom-sub-formresponse").html("<p class='text-danger'>An error occurred while getting the edit form.</p>").show();
    //         },
    //         complete: function () {
    //             $("#ajax-overlay").hide();
    //         }
    //     });
    // });
    

});
