<?php
namespace Scripto\Form;

use Laminas\Form\Form;

class ScriptoLoginForm extends Form
{
    public function init()
    {
        $this->setAttribute('class', 'disable-unsaved-warning');
        $this->add([
            'name' => 'scripto-username',
            'type' => 'text',
            'options' => [
                'label' => 'Username', // @translate
                'show_required' => false,
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'scripto-password',
            'type' => 'password',
            'options' => [
                'label' => 'Password', // @translate
                'show_required' => false,
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'scripto-login',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Log in', // @translate
            ],
        ]);
    }
}
