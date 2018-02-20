<?php
namespace Scripto\Form;

use Zend\Form\Form;

class UnimportProjectForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Unimport project', // @translate
            ],
        ]);
    }
}
