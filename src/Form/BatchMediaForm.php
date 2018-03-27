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
                    'review-selected' => [
                            'label' => 'Selected media', // @translate
                            'options' => [
                            [
                                'value' => 'approve-selected',
                                'label' => 'Mark as approved (selected)', // @translate
                                'attributes' => ['disabled' => true],
                            ],
                            [
                                'value' => 'unapprove-selected',
                                'label' => 'Mark as not approved (selected)', // @translate
                                'attributes' => ['disabled' => true],
                            ],
                            [
                                'value' => 'complete-selected',
                                'label' => 'Mark as completed (selected)', // @translate
                                'attributes' => ['disabled' => true],
                            ],
                            [
                                'value' => 'uncomplete-selected',
                                'label' => 'Mark as incomplete (selected)', // @translate
                                'attributes' => ['disabled' => true],
                            ],
                        ],
                    ],
                    'review-all' => [
                        'label' => 'All media', // @translate
                        'options' => [
                            'approve-all' => 'Mark as approved (all)', // @translate
                            'unapprove-all' => 'Mark as not approved (all)', // @translate
                            'complete-all' => 'Mark as completed (all)', // @translate
                            'uncomplete-all' => 'Mark as incomplete (all)', // @translate
                        ],
                    ],
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
                    'manage-selected' => [
                        'label' => 'Selected media', // @translate
                        'options' => [
                            [
                                'value' => 'watch-selected',
                                'label' => 'Add to your watchlist (selected)', // @translate
                                'attributes' => ['disabled' => true],
                            ],
                            [
                                'value' => 'unwatch-selected',
                                'label' => 'Remove from your watchlist (selected)', // @translate
                                'attributes' => ['disabled' => true],
                            ],
                            [
                                'value' => 'restrict-admin-selected',
                                'label' => 'Restrict editing to admins (selected)', // @translate
                                'attributes' => ['disabled' => true],
                            ],
                            [
                                'value' => 'restrict-user-selected',
                                'label' => 'Restrict editing to logged in users (selected)', // @translate
                                'attributes' => ['disabled' => true],
                            ],
                            [
                                'value' => 'open-selected',
                                'label' => 'Open editing to all users (selected)', // @translate
                                'attributes' => ['disabled' => true],
                            ],
                        ],
                    ],
                    'manage-all' => [
                        'label' => 'All media', // @translate
                        // Note that we're not providing batch-all options to
                        // restrict or open editing because the MediaWiki API
                        // has no batch protection feature.
                        'options' => [
                            'watch-all' => 'Add to your watchlist (all)', // @translate
                            'unwatch-all' => 'Remove from your watchlist (all)', // @translate
                        ],
                    ],
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
