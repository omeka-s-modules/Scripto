/**
 * Turn any textarea into a lightweight markup language (LML) editor.
 *
 * To translate button titles, add a "data-lml-editor-translations" data
 * attribute to the button container containing a JSON object where keys are
 * original strings and values are translated strings.
 *
 * @param textarea The editor textarea
 * @param buttonContainer The editor buttons container
 */
function LmlEditor(textarea, buttonContainer) {

    this.textarea = textarea;
    this.buttonContainer = buttonContainer;

    var translations = buttonContainer.dataset.lmlEditorTranslations
    this.translations = translations ? JSON.parse(translations) : {};

    /**
     * Create a LML button
     *
     * @param id Button ID
     * @param title Button title
     * @return Button element
     */
    this.createButton = function(id, title) {
        var button = document.createElement('button');
        title = (title in this.translations) ? this.translations[title] : title;
        button.id = id;
        button.title = title;
        button.appendChild(document.createTextNode(title));
        return button;
    };

    /**
     * Add a prepend/append button to the button container
     *
     * @param id Button ID
     * @param title Button title
     * @param prepend Prepend formatting string
     * @param append Append formatting string
     */
    this.addButton = function(id, title, prepend, append) {
        button = this.createButton(id, title);
        button.onclick = (e) => {
            e.preventDefault();
            this.replaceText(prepend, append);
        };
        this.buttonContainer.appendChild(button);
    };

    /**
     * Add a list button to the button container
     *
     * Provide an indentation character to maintain indentation. Otherwise, the
     * button will repeat the list character however many indentation levels.
     *
     * @param id Button ID
     * @param title Button title
     * @param listChar List character
     * @param indentChar Indentation character
     */
    this.addListButton = function(id, title, listChar, indentChar = null) {
        button = this.createButton(id, title);
        button.onclick = (e) => {
            e.preventDefault();
            this.listText(listChar, indentChar);
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
     * Replace selected text with a list
     *
     * @param listChar List character
     * @param indentChar Indentation character
     */
    this.listText = function(listChar, indentChar) {
        var textarea = this.textarea;
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var list = textarea.value.substring(start, end).split("\n");
        list.forEach(function(value, index, list) {
            var listLevel = value.search(/\S|$/);
            list[index] = ('string' === typeof(indentChar))
                ? indentChar.repeat(listLevel) + listChar + ' ' + value.trimStart()
                : listChar.repeat(listLevel + 1) + ' ' + value.trimStart();
        });
        textarea.value = textarea.value.slice(0, start) + list.join("\n") + textarea.value.slice(end);
        textarea.focus();
    };

    /**
     * Add MediaWiki buttons.
     *
     * @see https://www.mediawiki.org/wiki/Help:Formatting
     */
    this.addMediawikiButtons = function() {
        this.addButton('wikitext-editor-button-italic', 'Italic', "''", "''");
        this.addButton('wikitext-editor-button-bold', 'Bold', "'''", "'''");
        this.addButton('wikitext-editor-button-strike', 'Strike out', "<s>", "</s>");
        this.addButton('wikitext-editor-button-underline', 'Underline', "<u>", "</u>");
        this.addButton('wikitext-editor-button-blockquote', 'Blockquote', "<blockquote>\n", "\n</blockquote>");
        this.addButton('wikitext-editor-button-comment', 'Hidden comment', "<!-- ", " -->");
        this.addButton('wikitext-editor-button-heading-1', 'Level 1 heading', "\n= ", " =\n");
        this.addButton('wikitext-editor-button-heading-2', 'Level 2 heading', "\n== ", " ==\n");
        this.addButton('wikitext-editor-button-heading-3', 'Level 3 heading', "\n=== ", " ===\n");
        this.addButton('wikitext-editor-button-heading-4', 'Level 4 heading', "\n==== ", " ====\n");
        this.addButton('wikitext-editor-button-heading-5', 'Level 5 heading', "\n===== ", " =====\n");
        this.addButton('wikitext-editor-button-preformatted', 'Preformatted', "<pre>", "</pre>");
        this.addButton('wikitext-editor-button-horizontal-rule', 'Horizontal rule', "\n", "----\n");
        this.addButton('wikitext-editor-button-line-break', 'Line break', "\n", "<br>\n");
        this.addListButton('wikitext-editor-button-bullet-list', 'Bullet list', "*");
    };
}
