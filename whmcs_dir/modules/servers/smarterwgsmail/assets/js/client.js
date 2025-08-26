jQuery(document).ready(function ($) {
    function toggleManagementContent() {
        if ($("#managementsTab").hasClass("active")) {
            $("#managementsContent").css("display", "block");
        } else {
            $("#managementsContent").hide();
        }
    }

    toggleManagementContent();

    $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
        toggleManagementContent();
    });

    // management tabs click
    $(document).on("click", ".management-link", function(e) {
        e.preventDefault(); 
        
        let $this = $(this);
        let url   = $this.attr("href"); 
        
        $(".management-link").removeClass("active");
        $this.addClass("active");
        
        $("#managementContent").html('<div class="loading">Loading...</div>');
        
        $.get(url, function(response) {
            $("#managementContent").html(response);
        }).fail(function() {
            $("#managementContent").html('<div class="error">Failed to load content.</div>');
        });
    });
});

