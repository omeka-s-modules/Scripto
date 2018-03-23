<?php
namespace Scripto\Form;

use Zend\Form\Form;

class BatchMediaForm extends Form
{
    public function init()
    {
        $this->setAttribute('id', 'batch-form');
        $this->setAttribute('class', 'disable-unsaved-warning');

        $this->add([
            'type' => 'select',
            'name' => 'batch-review-action',
            'options' => [
                'value_options' => [
                    'default' => 'Batch review actions', // @translate
                    [
                        'value' => 'approve-selected',
                        'label' => 'Mark selected as approved', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => 'unapprove-selected',
                        'label' => 'Mark selected as not approved', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => 'complete-selected',
                        'label' => 'Mark selected as completed', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => 'uncomplete-selected',
                        'label' => 'Mark selected as incomplete', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    'approve-all' => 'Mark all as approved', // @translate
                    'unapprove-all' => 'Mark all as not approved', // @translate
                    'complete-all' => 'Mark all as completed', // @translate
                    'uncomplete-all' => 'Mark all as incomplete', // @translate
                ],
            ],
            'attributes' => [
                'class' => 'batch-review-select',
            ],
        ]);

        $this->add([
            'type' => 'select',
            'name' => 'batch-manage-action',
            'options' => [
                'value_options' => [
                    'default' => 'Batch manage actions', // @translate
                    [
                        'value' => 'watch-selected',
                        'label' => 'Watch selected', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => 'unwatch-selected',
                        'label' => 'Unwatch selected', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => 'protect-selected',
                        'label' => 'Protect selected', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => 'unprotect-selected',
                        'label' => 'Unprotect selected', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    'watch-all' => 'Watch all', // @translate
                    'unwatch-all' => 'Unwatch all', // @translate
                    'protect-all' => 'Protect all', // @translate
                    'unprotect-all' => 'Unprotect all', // @translate
                ],
            ],
            'attributes' => [
                'class' => 'batch-manage-select',
            ],
        ]);

        $this->add([
            'type' => 'submit',
            'name' => 'batch-review-submit',
            'attributes' => [
                'class' => 'batch-submit',
                'value' => 'Go', // @translate
                'formaction' => $this->getOption('batch-review-formaction'),
            ],
        ]);

        $this->add([
            'type' => 'submit',
            'name' => 'batch-manage-submit',
            'attributes' => [
                'class' => 'batch-submit',
                'value' => 'Go', // @translate
                'formaction' => $this->getOption('batch-manage-formaction'),
            ],
        ]);
    }
}
