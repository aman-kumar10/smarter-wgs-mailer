$(document).ready(function() {
    $('.nav-link.disabled').on('click', function(e) {
        $(this).css({
            cursor: 'not-allowed',
            color: '#6c757d'
        });
        $(this).attr('aria-disabled', 'true');
        
        e.preventDefault();
        return false;
    });
});