$(document).ready(function() {

// Handle the protection expiration select toggle.
var protectionSelect = $('[name="protection_level"]');
Scripto.toggleExpirationSelect(protectionSelect);
protectionSelect.on('change', function() {
    Scripto.toggleExpirationSelect($(this));
});

// Remove sidebar click event so revision pagination reloads the page.
$('#content').off('click', '.sidebar .pagination a');

$('button.save, button.edit').click(function() {
    $('#edit-view').toggle();
    $('#show-view').toggle();
});

});
