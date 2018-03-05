<?php
namespace Scripto\Form;

use Zend\Form\Form;

class ScriptoLogoutForm extends Form
{
    public function init()
    {
        $this->setAttribute('class', 'disable-unsaved-warning');
        $this->add([
            'name' => 'scripto-logout',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Log out', // @translate
            ],
        ]);
    }
}
