<?php
namespace Scripto\Form;

use Zend\Form\Form;

class RevertRevisionForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Confirm reversion', // @translate
            ],
        ]);
    }
}
