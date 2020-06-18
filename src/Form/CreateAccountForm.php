<?php
namespace Scripto\Form;

use Laminas\Form\Form;

class CreateAccountForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'text',
            'name' => 'username',
            'options' => [
                'label' => 'Username', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add([
            'type' => 'password',
            'name' => 'password',
            'options' => [
                'label' => 'Password', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add([
            'type' => 'password',
            'name' => 'retype',
            'options' => [
                'label' => 'Confirm password', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add([
            'type' => 'email',
            'name' => 'email',
            'options' => [
                'label' => 'Email address', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add([
            'type' => 'text',
            'name' => 'realname',
            'options' => [
                'label' => 'Real name', // @translate
            ],
        ]);
        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Create your account', // @translate
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'username',
            'required' => true,
        ]);
        $inputFilter->add([
            'name' => 'password',
            'required' => true,
        ]);
        $inputFilter->add([
            'name' => 'retype',
            'required' => true,
            'validators' => [
                [
                    'name' => 'identical',
                    'options' => [
                        'token' => 'password',
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'email',
            'required' => true,
        ]);
    }
}
