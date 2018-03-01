(function($) {

    $(document).on('o:expanded', '#page-action-menu a.collapse', function() {
        var button = $(this);
        $(document).on('mouseup.page-actions', function(e) {
            var pageActionMenu = $('#page-action-menu ul');
            if (pageActionMenu.is(e.target)) {
                return;
            }
            if (!button.is(e.target)) {
                button.click();
            }
            $(document).off('mouseup.page-actions');
        });
    });
})(jQuery)