$(document).ready(function() {

// Add wikitext editor buttons to the wikitext textarea.
var lmlEditor = new LmlEditor(
    document.getElementsByClassName('wikitext-editor-text')[0],
    document.getElementsByClassName('wikitext-editor-buttons')[0]
);
lmlEditor.addMediawikiButtons();

});
