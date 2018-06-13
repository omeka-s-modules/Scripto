$(document).ready(function() {

$('.menu-toggle').click(function(e) {
    e.preventDefault();
    var button = $(this);
    var menu = button.parent();
    var dropdown = button.next();
    menu.toggleClass('open');
    $(document).on('mouseup', button, function(e) {
        if (dropdown.is(e.target) || $.contains(dropdown[0], e.target)) {
            return;
        }
        if (!button.is(e.target)) {
            button.click();
        }
        $(document).off('mouseup', button);
    });
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
