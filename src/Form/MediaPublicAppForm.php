<?php
namespace Scripto\Form;

use Laminas\Form\Form;

class MediaPublicAppForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'textarea',
            'name' => 'wikitext',
            'attributes' => [
                'class' => 'wikitext-editor-text',
                'aria-label' => 'Wikitext', // @translate
            ],
        ]);

        $this->add([
            'type' => 'text',
            'name' => 'summary',
            'attributes' => [
                'id' => 'change-summary-text',
                'aria-label' => 'Change summary', // @translate
                'maxlength' => 255,
                'placeholder' => 'Summarize your changes', // @translate
            ],
        ]);

        $this->add([
            'type' => 'checkbox',
            'name' => 'mark_complete',
            'options' => [
                'label' => 'Mark complete', // @translate
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' => [
                'id' => 'mark_complete',
            ],
        ]);
        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Save', // @translate
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'mark_complete',
            'required' => false,
        ]);
    }
}
