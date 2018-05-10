var Scripto = {

    /**
     * Apply panzoom to an element.
     *
     * @param element
     */
    applyPanzoom: function(element) {
        var container = element.parent();
        if (!container.hasClass('image')) {
            return;
        }
        $panzoom = element.panzoom({
            $zoomIn: container.find(".zoom-in"),
            $zoomOut: container.find(".zoom-out"),
            $reset: container.find(".reset"),
            maxScale: 20
        });
        container.on('mousewheel.focal', function(e) {
            e.preventDefault();
            var delta = e.delta || e.originalEvent.wheelDelta;
            var zoomOut = delta ? delta < 0 : e.originalEvent.deltaY > 0;
            $panzoom.panzoom('zoom', zoomOut, {
                increment: 0.1,
                animate: false,
                focal: e
            });
        });
    },

    /**
     * Set rotation of an object.
     *
     * @param obj
     * @param direction
     */
    setRotation: function(obj, direction) {
        var matrix = obj.css("-webkit-transform")
            || obj.css("-moz-transform")
            || obj.css("-ms-transform")
            || obj.css("-o-transform")
            || obj.css("transform");
        if (matrix !== 'none') {
            var values = matrix.split('(')[1].split(')')[0].split(',');
            var a = values[0];
            var b = values[1];
            var angle = Math.round(Math.atan2(b, a) * (180/Math.PI));
        } else {
            var angle = 0;
        }
        var currentRotation = (angle < 0) ? angle + 360 : angle;
        var newRotation = (direction == 'left') ? currentRotation - 90 : currentRotation + 90;
        obj.css('transform', 'rotate(' + newRotation + 'deg)');
    },

    /**
     * Toggle the protection expiration select.
     *
     * @param protectionSelect
     */
    toggleExpirationSelect: function(protectionSelect) {
        var expirySelect = $('[name="protection_expiry"]');
        var currentValue = protectionSelect.find('option:selected').val();
        if (currentValue && currentValue != "all") {
            expirySelect.show();
        } else {
            expirySelect.hide();
        }
    }
};

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

    // Handle the sidebar drawers.
    var drawerButtonHtml = '<button type="button" class="sidebar-drawer collapse" aria-label="' + Omeka.jsTranslate('Collapse') + '">';
    $('.sidebar.always-open').prepend(drawerButtonHtml).addClass('expanded');

    $('.sidebar.always-open').on('click', '.sidebar-drawer', function() {
        var drawerButton = $(this);
        var sidebar = drawerButton.parents('.sidebar');
        $('#content').toggleClass('expanded');
        sidebar.toggleClass('expanded').toggleClass('collapsed');
        if (drawerButton.hasClass('collapse')) {
            drawerButton.removeClass('collapse').addClass('expand').attr('aria-label', Omeka.jsTranslate('Expand'));
        } else {
            drawerButton.removeClass('expand').addClass('collapse').attr('aria-label', Omeka.jsTranslate('Collapse'));
        }
    });
});

