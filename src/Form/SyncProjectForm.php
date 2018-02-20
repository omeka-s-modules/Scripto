<?php
namespace Scripto\Form;

use Zend\Form\Form;

class SyncProjectForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Sync project', // @translate
            ],
        ]);
    }
}
