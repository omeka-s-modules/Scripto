$(document).ready(function() {

// Handle a time period selection.
$('#time-period').on('change', function() {
    var thisSelect = $(this);
    var params = {'hours': thisSelect.val()};
    window.location.href = thisSelect.data('url') + '?' + $.param(params);
});

});
