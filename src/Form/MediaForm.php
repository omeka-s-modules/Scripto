<?php
namespace Scripto\Form;

use Scripto\Mediawiki\ApiClient;
use Zend\Form\Form;

class MediaForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'checkbox',
            'name' => 'is_completed',
            'options' => [
                'label' => 'Completed', // @translate
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' => [
                'id' => 'is_completed',
            ],
        ]);
        $this->add([
            'type' => 'checkbox',
            'name' => 'is_approved',
            'options' => [
                'label' => 'Approved', // @translate
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' => [
                'id' => 'is_approved',
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
            'type' => 'checkbox',
            'name' => 'is_watched',
            'options' => [
                'label' => 'Watchlist', // @translate
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ],
            'attributes' => [
                'id' => 'is_watched',
            ],
        ]);


        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'protection_expiry',
            'allow_empty' => true,
        ]);
    }
}
