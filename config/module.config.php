<?php
return [
    'service_manager' => [
        'factories' => [
            'Scripto\Mediawiki\ApiClient'  => Scripto\Service\Mediawiki\ApiClientFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/Scripto/view',
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Scripto\Controller\Index' => Scripto\Controller\IndexController::class,
            'Scripto\Controller\Admin\Index' => Scripto\Controller\Admin\IndexController::class,
        ],
    ],
    'form_elements' => [
        'factories' => [
            'Scripto\Form\ConfigForm' => Scripto\Service\Form\ConfigFormFactory::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            OMEKA_PATH . '/modules/Scripto/src/Entity',
        ],
        'proxy_paths' => [
            OMEKA_PATH . '/modules/Scripto/data/doctrine-proxies',
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'scripto_projects' => Scripto\Api\Adapter\ScriptoProjectAdapter::class,
            'scripto_items' => Scripto\Api\Adapter\ScriptoItemAdapter::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Scripto', // @translate
                'route' => 'admin/scripto',
                'resource' => 'Scripto\Controller\Admin\Index',
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'scripto' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/scripto',
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'Scripto\Controller\Admin\Index',
                                'action' => 'index',
                            ],
                        ],
                    ],
                ],
            ],
            'scripto' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/scripto',
                    'defaults' => [
                        'controller' => 'Scripto\Controller\Index',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'set' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:set-id',
                            'constraints' => [
                                'set-id' => '\d+',
                            ],
                            'defaults' => [
                                'action' => 'browse',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'item' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/:item-id',
                                    'constraints' => [
                                        'item-id' => '\d+',
                                    ],
                                    'defaults' => [
                                        'action' => 'show',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

        ],
    ],
];
