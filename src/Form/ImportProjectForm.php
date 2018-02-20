<?php
namespace Scripto\Form;

use Zend\Form\Form;

class ImportProjectForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Import project', // @translate
            ],
        ]);
    }
}
