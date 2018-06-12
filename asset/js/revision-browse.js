$(document).ready(function() {

// Hide and show checkboxes to prevent reverse comparisons (i.e. where
// from-revision-id is greater than to-revision-id).
$('input[name="from-revision-id"]').first().prop('checked', true).change();
$('input[name="to-revision-id"]').first().prop('checked', true).change();

$('input[name="from-revision-id"]').on('change', function() {
    var thisTr = $(this).closest('tr');
    thisTr.find('input[name="to-revision-id"]').hide();
    thisTr.prevAll().find('input[name="to-revision-id"]').show();
    thisTr.nextAll().find('input[name="to-revision-id"]').hide();
});

$('input[name="to-revision-id"]').on('change', function() {
    var thisTr = $(this).closest('tr');
    thisTr.find('input[name="from-revision-id"]').hide();
    thisTr.prevAll().find('input[name="from-revision-id"]').hide();
    thisTr.nextAll().find('input[name="from-revision-id"]').show();
});

$('button.compare-selected').on('click', function(e) {
    var fromId = $('input[name="from-revision-id"]:checked').val();
    var toId = $('input[name="to-revision-id"]:checked').val();
    window.location.href = $(this).data('url') + '/' + fromId + '/' + toId;
});

});
