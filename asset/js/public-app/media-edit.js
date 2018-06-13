$(document).ready(function() {

// Add wikitext editor buttons to the wikitext textarea.
var wikitextEditor = new WikitextEditor(
    document.getElementById('wikitext-editor-text'),
    document.getElementById('wikitext-editor-buttons')
);
wikitextEditor.addBasicButtons();

});
