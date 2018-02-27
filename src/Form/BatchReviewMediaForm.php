<?php
namespace Scripto\Form;

use Zend\Form\Form;

class BatchReviewMediaForm extends Form
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
                    'default' => 'Batch actions', // @translate
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
                'class' => 'batch-actions-select',
            ],
        ]);

        $this->add([
            'type' => 'submit',
            'name' => 'batch-review-submit',
            'attributes' => [
                'class' => 'batch-review-submit',
                'value' => 'Go', // @translate
                'formaction' => $this->getOption('formaction'),
            ],
        ]);
    }
}
