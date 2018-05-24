/**
 * Add wikitext editing buttons.
 *
 * @param textarea The wikitext textarea
 * @param buttonContainer The editor buttons container
 */
function WikitextEditor(textarea, buttonContainer) {

    this.textarea = textarea;
    this.buttonContainer = buttonContainer;

    /**
     * Add a wikitext button
     *
     * @param id Button ID
     * @param title Button title
     * @param prepend Prepend formatting string
     * @param append Append formatting string
     */
    this.addButton = function(id, title, prepend, append) {
        var button = document.createElement('button');
        button.id = id;
        button.title = title;
        button.appendChild(document.createTextNode(title));
        button.onclick = (e) => {
            e.preventDefault();
            this.replaceText(prepend, append);
        };
        this.buttonContainer.appendChild(button);
    };

    /**
     * Replace selected text or add text at cursor
     *
     * @param prepend Prepend formatting string
     * @param append Append formatting string
     */
    this.replaceText = function(prepend, append) {
        var textarea = this.textarea;
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var replacement = prepend + textarea.value.substring(start, end) + append;
        textarea.value = textarea.value.slice(0, start) + replacement + textarea.value.slice(end);
        textarea.focus();
    };

    /**
     * Add a basic set of buttons.
     *
     * @see https://www.mediawiki.org/wiki/Help:Formatting
     */
    this.addBasicButtons = function() {
        this.addButton('wikitext-editor-button-italic', 'Italic', "''", "''");
        this.addButton('wikitext-editor-button-bold', 'Bold', "'''", "'''");
        this.addButton('wikitext-editor-button-strike', 'Strike out', "<s>", "</s>");
        this.addButton('wikitext-editor-button-underline', 'Underline', "<u>", "</u>");
        this.addButton('wikitext-editor-button-preformatted', 'Preformatted', "<pre>", "</pre>");
        this.addButton('wikitext-editor-button-blockquote', 'Blockquote', "<blockquote>\n", "\n</blockquote>");
        this.addButton('wikitext-editor-button-horizontal-rule', 'Horizontal rule', "\n", "----\n");
        this.addButton('wikitext-editor-button-line-break', 'Line break', "\n", "<br>\n");
        this.addButton('wikitext-editor-button-comment', 'Hidden comment', "<!-- ", " -->");
        this.addButton('wikitext-editor-button-heading-1', 'Level 1 heading', "\n= ", " =\n");
        this.addButton('wikitext-editor-button-heading-2', 'Level 2 heading', "\n== ", " ==\n");
        this.addButton('wikitext-editor-button-heading-3', 'Level 3 heading', "\n=== ", " ===\n");
        this.addButton('wikitext-editor-button-heading-4', 'Level 4 heading', "\n==== ", " ====\n");
        this.addButton('wikitext-editor-button-heading-5', 'Level 5 heading', "\n===== ", " =====\n");
    };
}
