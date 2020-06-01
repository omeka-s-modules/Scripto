<?php
return [
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => OMEKA_PATH . '/modules/Scripto/language',
                'pattern' => '%s.mo',
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Scripto\Mediawiki\ApiClient' => Scripto\Service\Mediawiki\ApiClientFactory::class,
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
            'Scripto\Form\Element\MediaTypeSelect' => Scripto\Service\Form\Element\MediaTypeSelectFactory::class,
            'Scripto\Form\ModuleConfigForm' => Scripto\Service\Form\ModuleConfigFormFactory::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Scripto\Controller\PublicApp\User' => Scripto\Controller\PublicApp\UserController::class,
            'Scripto\Controller\PublicApp\Index' => Scripto\Controller\PublicApp\IndexController::class,
            'Scripto\Controller\PublicApp\Project' => Scripto\Controller\PublicApp\ProjectController::class,
            'Scripto\Controller\PublicApp\Item' => Scripto\Controller\PublicApp\ItemController::class,
            'Scripto\Controller\PublicApp\Media' => Scripto\Controller\PublicApp\MediaController::class,
            'Scripto\Controller\PublicApp\Revision' => Scripto\Controller\PublicApp\RevisionController::class,
            'Scripto\Controller\Admin\User' => Scripto\Controller\Admin\UserController::class,
            'Scripto\Controller\Admin\Item' => Scripto\Controller\Admin\ItemController::class,
            'Scripto\Controller\Admin\Media' => Scripto\Controller\Admin\MediaController::class,
            'Scripto\Controller\Admin\Revision' => Scripto\Controller\Admin\RevisionController::class,
        ],
        'factories' => [
            'Scripto\Controller\Admin\Index' => Scripto\Service\Controller\Admin\IndexControllerFactory::class,
            'Scripto\Controller\Admin\Project' => Scripto\Service\Controller\Admin\ProjectControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'scripto' => Scripto\Service\ControllerPlugin\ScriptoFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/Scripto/view',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'scripto' => Scripto\Service\ViewHelper\ScriptoFactory::class,
        ],
    ],
    'block_layouts' => [
        'invokables' => [
            'scripto' => Scripto\Site\BlockLayout\Scripto::class,
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'scripto' => Scripto\Site\Navigation\Link\Scripto::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Scripto', // @translate
                'route' => 'admin/scripto',
                'resource' => 'Scripto\Controller\Admin\Index',
                'privilege' => 'index',
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'scripto' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id][/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'index',
                        'action' => 'index',
                    ],
                ],
            ],
            'scripto-user-contributions' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/user/:user-id/contributions',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'user',
                        'action' => 'contributions',
                    ],
                ],
            ],
            'scripto-user-watchlist' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/user/:user-id/watchlist',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'user',
                        'action' => 'watchlist',
                    ],
                ],
            ],
            'scripto-project' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/project[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'project',
                        'action' => 'browse',
                    ],
                ],
            ],
            'scripto-project-id' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/:project-id[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'project-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'project',
                        'action' => 'show',
                    ],
                ],
            ],
            'scripto-item' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/:project-id/item[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'project-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'item',
                        'action' => 'browse',
                    ],
                ],
            ],
            'scripto-item-id' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/:project-id/:item-id[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'project-id' => '\d+',
                        'item-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'item',
                        'action' => 'show',
                    ],
                ],
            ],
            'scripto-media' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/:project-id/:item-id/media[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'project-id' => '\d+',
                        'item-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'media',
                        'action' => 'browse',
                    ],
                ],
            ],
            'scripto-media-id' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/:project-id/:item-id/:media-id[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'project-id' => '\d+',
                        'item-id' => '\d+',
                        'media-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'media',
                        'action' => 'show',
                    ],
                ],
            ],
            'scripto-revision' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/:project-id/:item-id/:media-id/revision[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'project-id' => '\d+',
                        'item-id' => '\d+',
                        'media-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'revision',
                        'action' => 'browse',
                    ],
                ],
            ],
            'scripto-revision-id' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/:project-id/:item-id/:media-id/revision/:revision-id[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'project-id' => '\d+',
                        'item-id' => '\d+',
                        'media-id' => '\d+',
                        'revision-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'revision',
                        'action' => 'show',
                    ],
                ],
            ],
            'scripto-revision-compare' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/:project-id/:item-id/:media-id/revision/:from-revision-id/:to-revision-id[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'project-id' => '\d+',
                        'item-id' => '\d+',
                        'media-id' => '\d+',
                        'from-revision-id' => '\d+',
                        'to-revision-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'revision',
                        'action' => 'compare',
                    ],
                ],
            ],
            'scripto-talk-media-id' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/:project-id/:item-id/:media-id/talk[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'project-id' => '\d+',
                        'item-id' => '\d+',
                        'media-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'media',
                        'action' => 'show-talk',
                    ],
                ],
            ],
            'scripto-talk-revision' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/:project-id/:item-id/:media-id/talk/revision[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'project-id' => '\d+',
                        'item-id' => '\d+',
                        'media-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'revision',
                        'action' => 'browse-talk',
                    ],
                ],
            ],
            'scripto-talk-revision-id' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/:project-id/:item-id/:media-id/talk/revision/:revision-id[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'project-id' => '\d+',
                        'item-id' => '\d+',
                        'media-id' => '\d+',
                        'revision-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'revision',
                        'action' => 'show-talk',
                    ],
                ],
            ],
            'scripto-talk-revision-compare' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/scripto[/s/:site-slug/:site-project-id]/:project-id/:item-id/:media-id/talk/revision/:from-revision-id/:to-revision-id[/:action]',
                    'constraints' => [
                        'site-slug' => '[a-zA-Z0-9_-]+',
                        'site-project-id' => '\d+',
                        'project-id' => '\d+',
                        'item-id' => '\d+',
                        'media-id' => '\d+',
                        'from-revision-id' => '\d+',
                        'to-revision-id' => '\d+',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Scripto\Controller\PublicApp',
                        'controller' => 'revision',
                        'action' => 'compare-talk',
                    ],
                ],
            ],
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
                    'scripto-user' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/user',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'user',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'scripto-user-contributions' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/user/:user-id/contributions',
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'user',
                                'action' => 'contributions',
                            ],
                        ],
                    ],
                    'scripto-user-watchlist' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/user/:user-id/watchlist',
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'user',
                                'action' => 'watchlist',
                            ],
                        ],
                    ],
                    'scripto-project' => [
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
                    'scripto-project-id' => [
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
                    'scripto-item' => [
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
                    'scripto-item-id' => [
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
                    'scripto-media' => [
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
                    'scripto-media-id' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/:project-id/:item-id/:media-id[/:revision-id][/:action]',
                            'constraints' => [
                                'project-id' => '\d+',
                                'item-id' => '\d+',
                                'media-id' => '\d+',
                                'revision-id' => '\d+',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'media',
                                'action' => 'show',
                            ],
                        ],
                    ],
                    'scripto-revision' => [
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
                    'scripto-revision-compare' => [
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
                    'scripto-talk-media-id' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/:project-id/:item-id/:media-id/talk[/:revision-id][/:action]',
                            'constraints' => [
                                'project-id' => '\d+',
                                'item-id' => '\d+',
                                'media-id' => '\d+',
                                'revision-id' => '\d+',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'media',
                                'action' => 'show-talk',
                            ],
                        ],
                    ],
                    'scripto-talk-revision' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/:project-id/:item-id/:media-id/talk/revision[/:action]',
                            'constraints' => [
                                'project-id' => '\d+',
                                'item-id' => '\d+',
                                'media-id' => '\d+',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Scripto\Controller\Admin',
                                'controller' => 'revision',
                                'action' => 'browse-talk',
                            ],
                        ],
                    ],
                    'scripto-talk-revision-compare' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/scripto/:project-id/:item-id/:media-id/talk/revision/:from-revision-id/:to-revision-id[/:action]',
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
                                'action' => 'compare-talk',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
