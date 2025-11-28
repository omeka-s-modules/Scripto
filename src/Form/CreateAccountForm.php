<?php
namespace Scripto\Form;

use Laminas\Form\Form;

class CreateAccountForm extends Form
{
    public function init()
    {
        $fields = $this->getOption('fields') ?? [];

        if (isset($fields['username'])) {
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
        }

        if (isset($fields['password'])) {
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
        }

        if (isset($fields['retype'])) {
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
        }

        if (isset($fields['email'])) {
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
        }

        if (isset($fields['realname'])) {
            $this->add([
                'type' => 'text',
                'name' => 'realname',
                'options' => [
                    'label' => 'Real name', // @translate
                ],
            ]);
        }

        if (isset($fields['captchaId'])) {
            $captchaId = $fields['captchaId']['value'];
            $this->add([
                'name' => 'captchaId',
                'type' => 'hidden',
                'attributes' => [
                    'value' => $captchaId,
                ],
            ]);
        }

        if (isset($fields['captchaWord'])) {
            $this->add([
                'name' => 'captchaWord',
                'type' => 'text',
                'options' => [
                    'label' => sprintf('CAPTCHA: %s', $fields['captchaInfo']['value']),
                ],
                'attributes' => [
                    'required' => true,
                ],
            ]);
        }

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Create your account', // @translate
            ],
        ]);

        $inputFilter = $this->getInputFilter();

        if (isset($fields['username'])) {
            $inputFilter->add([
                'name' => 'username',
                'required' => true,
            ]);
        }

        if (isset($fields['password'])) {
            $inputFilter->add([
                'name' => 'password',
                'required' => true,
            ]);
        }

        if (isset($fields['retype'])) {
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
        }

        if (isset($fields['email'])) {
            $inputFilter->add([
                'name' => 'email',
                'required' => true,
            ]);
        }
    }
}
