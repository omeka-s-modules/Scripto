(function($) {
    $(document).ready(function() {
    
        $('.layout button').click(function(e) {
            $('.layout button').toggleClass('active');
            $('.wikitext-featherlight').toggleClass('horizontal').toggleClass('vertical');
            $('.layout button:disabled').removeAttr('disabled');
            $('.layout button.active').attr('disabled', true);
        });
    
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
})(jQuery)

