var Scripto = {

    /**
     * Toggle the protection expiration select.
     *
     * @param protectionSelect
     */
    toggleExpirationSelect: function(protectionSelect) {
        var expirySelect = $('[name="protection_expiry"]');
        var currentValue = protectionSelect.find('option:selected').val();
        if (currentValue && currentValue != "all") {
            expirySelect.show();
        } else {
            expirySelect.hide();
        }
    },

    /**
     * Enable the LML editor for media edit.
     *
     * @param textarea
     * @param buttonContainer
     */
    enableMediaEditor: function(textarea, buttonContainer) {
        var lmlEditor = new LmlEditor(textarea[0], buttonContainer[0]);
        lmlEditor.addButton('wikitext-editor-button-bold', 'Bold', "'''", "'''");
        lmlEditor.addButton('wikitext-editor-button-italic', 'Italic', "''", "''");
        lmlEditor.addButton('wikitext-editor-button-underline', 'Underline', "<u>", "</u>");
        lmlEditor.addButton('wikitext-editor-button-strikethrough', 'Strikethrough', "<s>", "</s>");
        lmlEditor.addListButton('wikitext-editor-button-unordered-list', 'Unordered list', "*");
        lmlEditor.addListButton('wikitext-editor-button-ordered-list', 'Ordered list', "#");
        lmlEditor.addButton('wikitext-editor-button-blockquote', 'Blockquote', "<blockquote>\n", "\n</blockquote>");
        lmlEditor.addButton('wikitext-editor-button-heading-1', 'Level 1 heading', "\n= ", " =\n");
        lmlEditor.addButton('wikitext-editor-button-heading-2', 'Level 2 heading', "\n== ", " ==\n");
        lmlEditor.addButton('wikitext-editor-button-heading-3', 'Level 3 heading', "\n=== ", " ===\n");
        lmlEditor.addButton('wikitext-editor-button-heading-4', 'Level 4 heading', "\n==== ", " ====\n");
        lmlEditor.addButton('wikitext-editor-button-heading-5', 'Level 5 heading', "\n===== ", " =====\n");
        lmlEditor.addButton('wikitext-editor-button-horizontal-rule', 'Horizontal rule', "\n", "----\n");
    },

    /**
     * Enable the LML editor for media edit-talk.
     *
     * @param textarea
     * @param buttonContainer
     */
    enableMediaTalkEditor: function(textarea, buttonContainer) {
        var lmlEditor = new LmlEditor(textarea[0], buttonContainer[0]);
        lmlEditor.addButton('wikitext-editor-button-bold', 'Bold', "'''", "'''");
        lmlEditor.addButton('wikitext-editor-button-italic', 'Italic', "''", "''");
        lmlEditor.addButton('wikitext-editor-button-underline', 'Underline', "<u>", "</u>");
        lmlEditor.addButton('wikitext-editor-button-blockquote', 'Blockquote', "<blockquote>\n", "\n</blockquote>");
        lmlEditor.addButton('wikitext-editor-button-signature', 'Signature', "", buttonContainer[0].dataset.signature);
    }
};
