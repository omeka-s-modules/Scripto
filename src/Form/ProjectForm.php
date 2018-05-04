<?php
namespace Scripto\Form;

use Zend\Form\Form;
use Omeka\Form\Element\ItemSetSelect;
use Omeka\Form\Element\PropertySelect;

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

        $inputFilter = $this->getInputFilter();
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
    }
}
