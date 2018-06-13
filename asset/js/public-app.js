$(document).ready(function() {

$('.menu-toggle').on('click', function(e) {
    e.preventDefault();
    var thisMenu = $(this).parent();
    thisMenu.toggleClass('open');
    if (thisMenu.hasClass('sorting-toggle')) {
        $('.filtering-toggle').removeClass('open');
    } else if (thisMenu.hasClass('filtering-toggle')) {
        $('.sorting-toggle').removeClass('open');
    }
});

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

});
