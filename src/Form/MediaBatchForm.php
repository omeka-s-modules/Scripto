<?php
namespace Scripto\Form;

use Laminas\Form\Form;

class MediaBatchForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'is_completed',
            'type' => 'radio',
            'options' => [
                'label' => 'Completion status', // @translate
                'value_options' => [
                    '1' => 'Complete', // @translate
                    '0' => 'Incomplete', // @translate
                    '' => '[No change]', // @translate
                ],
            ],
            'attributes' => [
                'value' => '',
            ],
        ]);
        $this->add([
            'name' => 'is_approved',
            'type' => 'radio',
            'options' => [
                'label' => 'Approval status', // @translate
                'value_options' => [
                    '1' => 'Approved', // @translate
                    '0' => 'Not approved', // @translate
                    '' => '[No change]', // @translate
                ],
            ],
            'attributes' => [
                'value' => '',
            ],
        ]);
        $this->add([
            'type' => 'select',
            'name' => 'protection_level',
            'options' => [
                'label' => 'Protection level', // @translate
                'empty_option' => '[No change]', // @translate
                'value_options' => [
                    'all' => 'Allow all users', // @translate
                    'autoconfirmed' => 'Allow only confirmed users', // @translate
                    'sysop' => 'Allow only administrators', // @translate
                ],
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
            'name' => 'is_watched',
            'type' => 'radio',
            'options' => [
                'label' => 'Watchlist status', // @translate
                'value_options' => [
                    '1' => 'Watch', // @translate
                    '0' => 'Unwatch', // @translate
                    '' => '[No change]', // @translate
                ],
            ],
            'attributes' => [
                'value' => '',
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'is_completed',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'is_approved',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'protection_level',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'protection_expiry',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'is_watched',
            'allow_empty' => true,
        ]);
    }
}
