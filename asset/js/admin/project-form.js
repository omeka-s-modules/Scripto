$(document).ready(function() {

CKEDITOR.inline(document.getElementById('o-module-scripto-guidelines'));
CKEDITOR.inline(document.getElementById('o-module-scripto-create-account-text'));

var template = $($('#reviewer-row-template').data('template'));

// Add a row to the reviewers table.
var addReviewerRow = function(user) {
    $('#project-reviewers-table').show();
    var reviewerRow = template.clone();
    reviewerRow.find('.user-id').val(user['o:id']);
    reviewerRow.find('.user-name').text(user['o:name']);
    reviewerRow.find('.user-email').text(user['o:email']);
    $('#project-reviewers-table tbody').append(reviewerRow);
};

// Add existing reviewers to the reviewers table.
$.each($('#reviewers').data('reviewers'), function(index, user) {
    addReviewerRow(user);
});

// Add a new reviewer to the reviewers table.
$('#new-users').find('.selector-child').on('click', function(e) {
    var user = $(this).data('user');
    var reviewer = $('#project-reviewers-table').find('input.user-id[value="' + user['o:id'] + '"]');
    if (!reviewer.length) {
        // Do not add existing reviewers.
        addReviewerRow(user);
    }
});

$('#project-reviewers-table').on('click', '.o-icon-delete, .o-icon-undo', function(e) {
    e.preventDefault();
    var thisIcon = $(this);
    thisIcon.parents('tr').toggleClass('delete');
});
$('#project-reviewers-table').on('click', '.o-icon-delete', function(e) {
    e.preventDefault();
    var thisIcon = $(this);
    var row = thisIcon.parents('tr');
    thisIcon.hide();
    row.find('.o-icon-undo').show();
    row.find('input[type="hidden"]').prop('disabled', true);
});
$('#project-reviewers-table').on('click', '.o-icon-undo', function(e) {
    var thisIcon = $(this);
    var row = thisIcon.parents('tr');
    thisIcon.hide();
    row.find('.o-icon-delete').show();
    row.find('input[type="hidden"]').prop('disabled', false);
});

});
