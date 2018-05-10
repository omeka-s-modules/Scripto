$(document).ready(function() {

    // Apply panzoom and featherlight.
    if ($('.image.panzoom-container').length) {

        var storedPanzoomStyle = '';
        var storedRotateStyle = '';

        Scripto.applyPanzoom($('#wikitext .media-render'));

        $('.full-screen').featherlight('.wikitext-featherlight', {
            beforeOpen: function() {
                $('#wikitext .media-render').panzoom('destroy');
            },
            afterOpen: function() {
                Scripto.applyPanzoom($('.featherlight-content .media-render'));
            },
            beforeClose: function() {
                storedPanzoomStyle = $('.featherlight-content .media-render').attr('style');
                storedRotateStyle = $('.featherlight-content .panzoom-container img').attr('style');
                $('.featherlight-content .media-render').panzoom('destroy');
                $('#wikitext .media-render').attr('style', storedPanzoomStyle);
                $('#wikitext .panzoom-container img').attr('style', storedRotateStyle);
            },
            afterClose: function() {
                Scripto.applyPanzoom($('#wikitext .media-render'));
            }
        });

        $('.panzoom-container').on('click', '.rotate-left', function(e) {
            e.preventDefault();
            var panzoomImg = $(this).parents('.panzoom-container').find('img');
            Scripto.setRotation(panzoomImg, 'left');
        });

        $('.panzoom-container').on('click', '.rotate-right', function(e) {
            e.preventDefault();
            var panzoomImg = $(this).parents('.panzoom-container').find('img');
            Scripto.setRotation(panzoomImg, 'right');
        });

        $('.panzoom-container').on('click', '.reset', function(e) {
            e.preventDefault();
            var panzoomImg = $(this).parents('.panzoom-container').find('img');
            panzoomImg.css('transform', 'none');
        });
    } else {
        $('.full-screen').featherlight('.wikitext-featherlight');
    }

    // Handle the layout buttons.
    $('.layout button').click(function(e) {
        $('.layout button').toggleClass('active');
        $('.wikitext-featherlight').toggleClass('horizontal').toggleClass('vertical');
        $('.layout button:disabled').removeAttr('disabled');
        $('.layout button.active').attr('disabled', true);
    });

    // Handle the protection expiration select toggle.
    var protectionSelect = $('[name="protection_level"]');
    Scripto.toggleExpirationSelect(protectionSelect);
    protectionSelect.on('change', function() {
        Scripto.toggleExpirationSelect($(this));
    });

    // Handle the watchlist toggle.
    $('#content').on('click', '.watchlist.button', function(e) {
        e.preventDefault();
        var watchlistIcon = $(this);
        $(this).toggleClass('watched')
        var watchlistHiddenValue = $(this).next('[type="hidden"]');
        if (watchlistHiddenValue.val() == 0) {
            watchlistIcon.attr('aria-label', Omeka.jsTranslate('Watch media'));
            watchlistIcon.attr('title', Omeka.jsTranslate('Watch media'));
            watchlistHiddenValue.attr('value', 1);
        } else {
            watchlistIcon.attr('aria-label', Omeka.jsTranslate('Stop watching media'));
            watchlistIcon.attr('title', Omeka.jsTranslate('Stop watching media'));
            watchlistHiddenValue.attr('value', 0);
        }
    });

    // Remove sidebar click event so revision pagination reloads the page.
    $('#content').off('click', '.sidebar .pagination a');
});
