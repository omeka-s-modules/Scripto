$(document).ready(function() {

// Show "back to top" link when scrolling past window height.

var backToTop    = $("#back-to-top"),
    $window = $(window),
    content = $("#content");

var checkScroll = function () {
    if ($window.scrollTop() > content.offset().top) {
        backToTop.addClass("active");
    } else {
        backToTop.removeClass("active");
    }
}

checkScroll();

$window.resize( function() {
    checkScroll();
});

$window.scroll( function() {
   checkScroll(); 
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
    var toggleParentCheck = $(e.target).parent().parent().is('.sorting-toggle, .filtering-toggle');
    var toggleOptionCheck = $(e.target).is('option');
    if (toggleParentCheck || toggleOptionCheck) {
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

// Toggle media's original titles.
var mediaToggle = $('.title-toggle input[type="checkbox"]');
$(document).on('change', mediaToggle, function() {
   $('.original-title').toggleClass('active'); 
});

$(document).trigger('enhance.tablesaw');

});
