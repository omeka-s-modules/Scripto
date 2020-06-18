<?php
namespace Scripto\Form;

use Omeka\Form\Element\ItemSetSelect;
use Omeka\Form\Element\PropertySelect;
use Scripto\Form\Element\MediaTypeSelect;
use Laminas\Form\Form;

class ProjectForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'o-module-scripto:title',
            'type' => 'text',
            'options' => [
                'label' => 'Title', // @translate
                'info' => 'Enter the title of this project.', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'o-module-scripto-title',
            ],
        ]);

        $this->add([
            'name' => 'o:item_set',
            'type' => ItemSetSelect::class,
            'options' => [
                'label' => 'Item set', // @translate
                'info' => 'Select the item set used to synchronize project items. Once synchronized, this project will contain every item in this item set.', // @translate
                'empty_option' => '',
                'show_required' => true,
            ],
            'attributes' => [
                'class' => 'chosen-select',
                'data-placeholder' => 'Select an item set', // @translate
                'id' => 'o-item-set',
            ],
        ]);

        $this->add([
            'name' => 'o:property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Property', // @translate
                'info' => 'Select the property used to store imported content.', // @translate
                'empty_option' => '',
                'show_required' => true,
            ],
            'attributes' => [
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
                'id' => 'o-property',
            ],
        ]);

        $this->add([
            'name' => 'o-module-scripto:media_types',
            'type' => MediaTypeSelect::class,
            'options' => [
                'label' => 'Media types', // @translate
                'info' => 'Select media types to include in the project. If empty, all media will be included.', // @translate
                'empty_option' => '[Media without media type]', // @translate
                'disable_inarray_validator' => true,
            ],
            'attributes' => [
                'id' => 'o-module-scripto-media_types',
                'multiple' => true,
                'required' => false,
                'class' => 'chosen-select',
                'data-placeholder' => 'Select media types', // @translate
            ],
        ]);

        $this->add([
            'name' => 'o-module-scripto:description',
            'type' => 'textarea',
            'options' => [
                'label' => 'Description', // @translate
                'info' => 'Enter the description of this project.', // @translate
            ],
            'attributes' => [
                'id' => 'o-module-scripto-description',
            ],
        ]);

        $this->add([
            'name' => 'o-module-scripto:guidelines',
            'type' => 'textarea',
            'options' => [
                'label' => 'Guidelines', // @translate
                'info' => 'Enter guidelines for producing content for this project.', // @translate
            ],
            'attributes' => [
                'id' => 'o-module-scripto-guidelines',
            ],
        ]);

        $this->add([
            'name' => 'o-module-scripto:create_account_text',
            'type' => 'textarea',
            'options' => [
                'label' => 'Create account text', // @translate
                'info' => 'Enter text for the create account page of this project.', // @translate
            ],
            'attributes' => [
                'id' => 'o-module-scripto-create-account-text',
            ],
        ]);

        $this->add([
            'name' => 'o:lang',
            'type' => 'text',
            'options' => [
                'label' => 'Language tag', // @translate
                'info' => 'Enter the language of your content using an IETF language tag.', // @translate
            ],
            'attributes' => [
                'id' => 'o-lang',
            ],
        ]);

        $this->add([
            'name' => 'o-module-scripto:import_target',
            'type' => 'select',
            'options' => [
                'label' => 'Import target', // @translate
                'info' => 'Select the target resource(s) where imported content will be stored.',
                'empty_option' => 'Item and media', // @translate
                'value_options' => [
                    'item' => 'Item', // @translate
                    'media' => 'Media', // @translate
                ],
            ],
        ]);

        $this->add([
            'name' => 'o-module-scripto:browse_layout',
            'type' => 'select',
            'options' => [
                'label' => 'Browse layout', // @translate
                'info' => 'Select the default layout for public browse views.', // @translate
                'value_options' => [
                    'grid' => 'Grid', // @translate
                    'list' => 'List', // @translate
                ],
            ],
        ]);

        $this->add([
            'name' => 'o-module-scripto:filter_approved',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Filter approved', // @translate
                'info' => 'Filter out approved items from the public browse view.', // @translate
            ],
        ]);

        $this->add([
            'name' => 'o-module-scripto:item_type',
            'type' => 'select',
            'options' => [
                'label' => 'Item type', // @translate
                'info' => 'Select the type of item covered by this project. This is used to clarify the interface, if needed.', // @translate
                'empty_option' => 'Generic item', // @translate
                'value_options' => [
                    'audio' => 'Audio', // @translate
                    'book' => 'Book', // @translate
                    'document' => 'Document', // @translate
                    'journal' => 'Journal', // @translate
                    'manuscript' => 'Manuscript', // @translate
                    'paper' => 'Paper', // @translate
                    'video' => 'Video', // @translate
                ],
            ],
        ]);

        $this->add([
            'name' => 'o-module-scripto:media_type',
            'type' => 'select',
            'options' => [
                'label' => 'Media type', // @translate
                'info' => 'Select the type of media covered by this project. This is used to clarify the interface, if needed.', // @translate
                'empty_option' => 'Generic media', // @translate
                'value_options' => [
                    'entry' => 'Entry', // @translate
                    'folio' => 'Folio', // @translate
                    'image' => 'Image', // @translate
                    'page' => 'Page', // @translate
                    'section' => 'Section', // @translate
                    'segment' => 'Segment', // @translate
                    'sheet' => 'Sheet', // @translate
                ],
            ],
        ]);

        $this->add([
            'name' => 'o-module-scripto:content_type',
            'type' => 'select',
            'options' => [
                'label' => 'Content type', // @translate
                'info' => 'Select the type of content covered by this project. This is used to clarify the interface, if needed.', // @translate
                'empty_option' => 'Generic content', // @translate
                'value_options' => [
                    'description' => 'Description', // @translate
                    'transcription' => 'Transcription', // @translate
                    'translation' => 'Translation', // @translate
                ],
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o-module-scripto:media_types',
            'required' => false,
            'filters' => [
                ['name' => 'toNull'],
            ],
        ]);
        $inputFilter->add([
            'name' => 'o-module-scripto:description',
            'required' => false,
            'filters' => [
                ['name' => 'toNull'],
            ],
        ]);
        $inputFilter->add([
            'name' => 'o-module-scripto:guidelines',
            'required' => false,
            'filters' => [
                ['name' => 'toNull'],
            ],
        ]);
        $inputFilter->add([
            'name' => 'o-module-scripto:create_account_text',
            'required' => false,
            'filters' => [
                ['name' => 'toNull'],
            ],
        ]);
        $inputFilter->add([
            'name' => 'o-module-scripto:import_target',
            'allow_empty' => true,
            'filters' => [
                ['name' => 'toNull'],
            ],
        ]);
        $inputFilter->add([
            'name' => 'o:lang',
            'required' => false,
            'filters' => [
                ['name' => 'toNull'],
            ],
        ]);
        $inputFilter->add([
            'name' => 'o-module-scripto:item_type',
            'allow_empty' => true,
            'filters' => [
                ['name' => 'toNull'],
            ],
        ]);
        $inputFilter->add([
            'name' => 'o-module-scripto:media_type',
            'allow_empty' => true,
            'filters' => [
                ['name' => 'toNull'],
            ],
        ]);
        $inputFilter->add([
            'name' => 'o-module-scripto:content_type',
            'allow_empty' => true,
            'filters' => [
                ['name' => 'toNull'],
            ],
        ]);
    }
}
