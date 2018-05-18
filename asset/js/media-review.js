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
    var isWatched = $('input[type="hidden"][name="is_watched"]');
    var watchedIcon = isWatched.siblings('.watchlist.button.watched');
    var notWatchedIcon = isWatched.siblings('.watchlist.button').not('.watched');
    '1' === isWatched.val() ? watchedIcon.show() : notWatchedIcon.show();
    watchedIcon.add(notWatchedIcon).on('click', function(e) {
        e.preventDefault();
        if ('1' === isWatched.val()) {
            isWatched.val('0');
            notWatchedIcon.show();
            watchedIcon.hide();
        } else {
            isWatched.val('1');
            notWatchedIcon.hide()
            watchedIcon.show();
        }
    });

    // Remove sidebar click event so revision pagination reloads the page.
    $('#content').off('click', '.sidebar .pagination a');

    $('button.save, button.edit').click(function() {
        $('#edit-view').toggle();
        $('#show-view').toggle();
    });
});
