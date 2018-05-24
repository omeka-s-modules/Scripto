$(document).ready(function() {
    $('.menu-toggle').click(function(e) {
        e.preventDefault();
        var button = $(this);
        var menu = button.parent();
        var dropdown = button.next();
        menu.toggleClass('open');
        $(document).on('mouseup.menu-toggle', function(e) {
            if (dropdown.is(e.target) || $.contains(dropdown[0], e.target)) {
                return;
            }
            if (!button.is(e.target)) {
                button.click();
            }
            $(document).off('mouseup.menu-toggle');
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

    // Add wikitext editor buttons to the wikitext textarea.
    $('#show-view button.edit').on('click', function(e) {
        var wikitextEditor = new WikitextEditor(
            document.getElementById('wikitext-editor-text'),
            document.getElementById('wikitext-editor-buttons')
        );
        wikitextEditor.addBasicButtons();
    });
});
