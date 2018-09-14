$(document).ready(function() {

// Toggle project search form.
$('.project-nav .o-icon-search').click(function(e) {
    e.preventDefault();
    $('#scripto-search').toggleClass('active');
    $('#scripto-search input[type="text"]').focus();
});

// Toggle the sort and filter menus.
$('.menu-toggle').on('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var thisMenu = $(this).parent();
    // Set a high z-index so subsequent in-menu clicks don't fall through.
    thisMenu.toggleClass('open').css('z-index', 100);
    if (thisMenu.hasClass('sorting-toggle')) {
        $('.filtering-toggle').removeClass('open');
    } else if (thisMenu.hasClass('filtering-toggle')) {
        $('.sorting-toggle').removeClass('open');
    }
});

// Close the sort and filter menus by clicking anywhere outside them.
$(document).on('click', function(e) {
    if ($(e.target).parent().parent().is('.sorting-toggle, .filtering-toggle')) {
        // Ignore if clicks are triggered from in-menu selects.
        return;
    }
    $('.filtering-toggle, .sorting-toggle').removeClass('open');
});

// Handle the list and grid buttons.
var listButton = $('.list-layout .list');
var gridButton = $('.list-layout .grid');
listButton.click(function() {
    $('.resource-list table').show();
    $('.resource-list .resource-grid').hide();
    listButton.attr('disabled', true);
    gridButton.removeAttr('disabled');
});

gridButton.click(function() {
    $('.resource-list table').hide();
    $('.resource-list .resource-grid').show();
    gridButton.attr('disabled', true);
    listButton.removeAttr('disabled');
});

$(document).trigger('enhance.tablesaw');

});
