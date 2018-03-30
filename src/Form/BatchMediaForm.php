<?php
namespace Scripto\Form;

use Scripto\Mediawiki\ApiClient;
use Zend\Form\Form;

class BatchMediaForm extends Form
{
    /**
     * @var ApiClient
     */
    protected $client;

    public function init()
    {
        $this->setAttribute('id', 'batch-form');
        $this->setAttribute('class', 'disable-unsaved-warning');

        $allOptions = [
            'approve-all' => 'Mark as approved (all)', // @translate
            'unapprove-all' => 'Mark as not approved (all)', // @translate
            'complete-all' => 'Mark as completed (all)', // @translate
            'uncomplete-all' => 'Mark as incomplete (all)', // @translate
        ];
        $selectedOptions = [
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
        ];

        // User must be logged in to add pages to their watchlist.
        if ($this->client->userIsLoggedIn()) {
            $selectedOptions[] = [
                'value' => 'watch-selected',
                'label' => 'Add to your watchlist (selected)', // @translate
                'attributes' => ['disabled' => true],
            ];
            $selectedOptions[] = [
                'value' => 'unwatch-selected',
                'label' => 'Remove from your watchlist (selected)', // @translate
                'attributes' => ['disabled' => true],
            ];
            $allOptions['watch-all'] = 'Add to your watchlist (all)'; // @translate
            $allOptions['unwatch-all'] = 'Remove from your watchlist (all)'; // @translate
        }

        $this->add([
            'type' => 'select',
            'name' => 'batch-manage-action',
            'options' => [
                'empty_option' => 'Batch manage actions:', // @translate
                'value_options' => [
                    'manage-all' => [
                        'label' => 'All media', // @translate
                        'options' => $allOptions,
                    ],
                    'manage-selected' => [
                        'label' => 'Selected media', // @translate
                        'options' => $selectedOptions,
                    ],
                ],
            ],
            'attributes' => [
                'id' => 'batch-manage-select',
            ],
        ]);

        $this->add([
            'type' => 'select',
            'name' => 'batch-protect-action',
            'options' => [
                'empty_option' => 'Batch edit protection actions:', // @translate
                'value_options' => [
                    [
                        'value' => 'all',
                        'label' => 'Allow all users (selected)', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => 'autoconfirmed',
                        'label' => 'Allow only confirmed users (selected)', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => 'sysop',
                        'label' => 'Allow only administrators (selected)', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                ],
            ],
            'attributes' => [
                'id' => 'batch-protect-select',
            ],
        ]);
        $this->add([
            'type' => 'select',
            'name' => 'batch-protect-expiry',
            'options' => [
                'empty_option' => 'Expires:', // @translate
                'value_options' => [
                    [
                        'value' => 'never',
                        'label' => 'never', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => '1 hour',
                        'label' => '1 hour', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => '1 day',
                        'label' => '1 day', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => '1 week',
                        'label' => '1 week', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => '2 weeks',
                        'label' => '2 weeks', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => '1 month',
                        'label' => '1 month', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => '3 months',
                        'label' => '3 months', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => '6 months',
                        'label' => '6 months', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'value' => '1 year',
                        'label' => '1 year', // @translate
                        'attributes' => ['disabled' => true],
                    ],
                ],
            ],
            'attributes' => [
                'id' => 'batch-protect-expiry-select',
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

        $this->add([
            'type' => 'submit',
            'name' => 'batch-protect-submit',
            'attributes' => [
                'class' => 'batch-submit',
                'value' => 'Go', // @translate
                'formaction' => $this->getOption('batch-protect-formaction'),
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'batch-manage-action',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'batch-protect-action',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'batch-protect-expiry',
            'allow_empty' => true,
        ]);
    }

    /**
     * Set the MediaWiki API client.
     *
     * @param ApiClient $client
     */
    public function setApiClient(ApiClient $client)
    {
        $this->client = $client;
    }
}
