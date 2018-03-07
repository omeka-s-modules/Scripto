<?php
return [
    'service_manager' => [
        'factories' => [
            'Scripto\Mediawiki\ApiClient'  => Scripto\Service\Mediawiki\ApiClientFactory::class,
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'scripto_projects' => Scripto\Api\Adapter\ScriptoProjectAdapter::class,
            'scripto_items' => Scripto\Api\Adapter\ScriptoItemAdapter::class,
            'scripto_media' => Scripto\Api\Adapter\ScriptoMediaAdapter::class,
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
    'form_elements' => [
        'factories' => [
            'Scripto\Form\ConfigForm' => Scripto\Service\Form\ConfigFormFactory::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Scripto\Controller\Index' => Scripto\Controller\IndexController::class,
            'Scripto\Controller\Admin\Index' => Scripto\Controller\Admin\IndexController::class,
            'Scripto\Controller\Admin\User' => Scripto\Controller\Admin\UserController::class,
            'Scripto\Controller\Admin\Project' => Scripto\Controller\Admin\ProjectController::class,
            'Scripto\Controller\Admin\Item' => Scripto\Controller\Admin\ItemController::class,
            'Scripto\Controller\Admin\Media' => Scripto\Controller\Admin\MediaController::class,
            'Scripto\Controller\Admin\Revision' => Scripto\Controller\Admin\RevisionController::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'scriptoApiClient' => Scripto\Service\ControllerPlugin\ScriptoApiClientFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/Scripto/view',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'scriptoBreadcrumbs' => Scripto\Service\ViewHelper\ScriptoBreadcrumbsFactory::class,
            'scriptoAuth' => Scripto\Service\ViewHelper\ScriptoAuthFactory::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Scripto', // @translate
                'route' => 'admin/scripto-project',
                'resource' => 'Scripto\Controller\Admin\Project',
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'scripto' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'index',
                                'action' => 'index',
                            ],
                        ],
                    ],
                    'scripto-user-id' =>  [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/user/:user-id',
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'user',
                                'action' => 'show',
                            ],
                        ],
                    ],
                    'scripto-project' =>  [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/project[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'project',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'scripto-project-id' =>  [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/:project-id[/:action]',
                            'constraints' => [
                                'project-id' => '\d+',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'project',
                                'action' => 'show',
                            ],
                        ],
                    ],
                    'scripto-item' =>  [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/:project-id/item[/:action]',
                            'constraints' => [
                                'project-id' => '\d+',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'item',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'scripto-item-id' =>  [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/:project-id/:item-id[/:action]',
                            'constraints' => [
                                'project-id' => '\d+',
                                'item-id' => '\d+',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'item',
                                'action' => 'show',
                            ],
                        ],
                    ],
                    'scripto-media' =>  [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/:project-id/:item-id/media[/:action]',
                            'constraints' => [
                                'project-id' => '\d+',
                                'item-id' => '\d+',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'media',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'scripto-media-id' =>  [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/:project-id/:item-id/:media-id[/:action]',
                            'constraints' => [
                                'project-id' => '\d+',
                                'item-id' => '\d+',
                                'media-id' => '\d+',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'media',
                                'action' => 'show',
                            ],
                        ],
                    ],
                    'scripto-revision' =>  [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/:project-id/:item-id/:media-id/revision[/:action]',
                            'constraints' => [
                                'project-id' => '\d+',
                                'item-id' => '\d+',
                                'media-id' => '\d+',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'revision',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'scripto-revision-id' =>  [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/:project-id/:item-id/:media-id/revision/:revision-id[/:action]',
                            'constraints' => [
                                'project-id' => '\d+',
                                'item-id' => '\d+',
                                'media-id' => '\d+',
                                'revision-id' => '\d+',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'revision',
                                'action' => 'show',
                            ],
                        ],
                    ],
                    'scripto-revision-compare' =>  [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/:project-id/:item-id/:media-id/revision/:from-revision-id/:to-revision-id[/:action]',
                            'constraints' => [
                                'project-id' => '\d+',
                                'item-id' => '\d+',
                                'media-id' => '\d+',
                                'from-revision-id' => '\d+',
                                'to-revision-id' => '\d+',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'revision',
                                'action' => 'compare',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
