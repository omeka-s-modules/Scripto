<?php
return [
    'service_manager' => [
        'factories' => [
            'Scripto\Mediawiki\ApiClient'  => 'Scripto\Service\Mediawiki\ApiClientFactory',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/Scripto/view',
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Scripto\Controller\SiteAdmin\Index' => 'Scripto\Controller\SiteAdmin\IndexController',
        ],
    ],
    'navigation' => [
        'site' => [
            [
                'label' => 'Scripto', // @translate
                'route' => 'admin/site/slug/scripto',
                'action' => 'index',
                'useRouteMatch' => true,
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'site' => [
                        'child_routes' => [
                            'slug' => [
                                'child_routes' => [
                                    'scripto' => [
                                        'type' => 'Literal',
                                        'options' => [
                                            'route' => '/scripto',
                                            'defaults' => [
                                                '__NAMESPACE__' => 'Scripto\Controller\SiteAdmin',
                                                'controller' => 'Index',
                                                'action' => 'index',
                                            ],
                                        ],
                                        'may_terminate' => true,
                                        'child_routes' => [],
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
