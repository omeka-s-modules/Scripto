$(document).ready(function() {

// Add wikitext editor buttons to the wikitext textarea.
var lmlEditor = new LmlEditor(
    document.getElementById('wikitext-editor-text'),
    document.getElementById('wikitext-editor-buttons')
);
lmlEditor.addMediawikiButtons();

});
