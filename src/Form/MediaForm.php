<?php
namespace Scripto\Form;

use Laminas\Form\Form;

class MediaForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'checkbox',
            'name' => 'toggle_complete',
            'options' => [
                'label' => 'Toggle complete', // @translate
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' => [
                'id' => 'toggle_complete',
            ],
        ]);
        $this->add([
            'type' => 'select',
            'name' => 'complete_action',
            'options' => [
                'label' => 'Complete action', // @translate
                'empty_option' => 'Select action:', // @translate
                'value_options' => [
                    'complete' => 'Mark this revision as complete', // @translate
                    'not_complete' => 'Mark media as not complete', // @translate
                ],
            ],
        ]);
        $this->add([
            'type' => 'checkbox',
            'name' => 'toggle_approved',
            'options' => [
                'label' => 'Toggle approved', // @translate
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' => [
                'id' => 'toggle_approved',
            ],
        ]);
        $this->add([
            'type' => 'select',
            'name' => 'approved_action',
            'options' => [
                'label' => 'Approved action', // @translate
                'empty_option' => 'Select action:', // @translate
                'value_options' => [
                    'approved' => 'Mark this revision as approved', // @translate
                    'not_approved' => 'Mark media as not approved', // @translate
                ],
            ],
        ]);
        $this->add([
            'type' => 'select',
            'name' => 'protection_level',
            'options' => [
                'label' => 'Protection level', // @translate
                'value_options' => [
                    'all' => 'Allow all users', // @translate
                    'autoconfirmed' => 'Allow only confirmed users', // @translate
                    'sysop' => 'Allow only administrators', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'protection_level',
            ],
        ]);
        $this->add([
            'type' => 'select',
            'name' => 'protection_expiry',
            'options' => [
                'label' => 'Protection expiry', // @translate
                'value_options' => [
                    'infinite' => 'infinite', // @translate
                    '1 hour' => '1 hour', // @translate
                    '1 day' => '1 day', // @translate
                    '1 week' => '1 week', // @translate
                    '2 weeks' => '2 weeks', // @translate
                    '1 month' => '1 month', // @translate
                    '3 months' => '3 months', // @translate
                    '6 months' => '6 months', // @translate
                    '1 year' => '1 year', // @translate
                ],
            ],
        ]);
        $this->add([
            'name' => 'submit_mediaform',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Save', // @translate
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'toggle_complete',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'complete_action',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'toggle_approved',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'approved_action',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'protection_level',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'protection_expiry',
            'allow_empty' => true,
        ]);
    }
}
