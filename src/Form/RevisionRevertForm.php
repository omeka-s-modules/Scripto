<?php
namespace Scripto\Form;

use Zend\Form\Form;

class RevisionRevertForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'submit_revisionrevertform',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Confirm reversion', // @translate
            ],
        ]);
    }
}
