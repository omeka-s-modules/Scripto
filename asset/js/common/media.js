$(document).ready(function() {

// Handle the watchlist toggle.
var watchlist = $('.watch-list');
var watchedIcon = watchlist.children('.watchlist.button.watched');
var notWatchedIcon = watchlist.children('.watchlist.button').not('.watched');
var watching = watchlist.data('watching');

watchlist.children('.watchlist.button').on('click', function(e) {
    e.preventDefault();
    watching = (1 === watching) ? 0 : 1;
    $.post(watchlist.data('url'), {'watching': watching})
        .done(function(data) {
            watchedIcon.toggle();
            notWatchedIcon.toggle();
            if (watching) {
                watchlist.children('.watch.success').fadeIn('slow').delay(2000).fadeOut('slow');
            } else {
                watchlist.children('.unwatch.success').fadeIn('slow').delay(2000).fadeOut('slow');
            }
        });
});

});
