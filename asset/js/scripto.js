$(document).ready(function() {

    // Handle page action menu.
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

    // Close all sidebars before opening another.
    $('a.sidebar-content').on('click', function(e) {
        var sidebars = $('.sidebar').each(function() {
            Omeka.closeSidebar($(this));
        });
    });

});
