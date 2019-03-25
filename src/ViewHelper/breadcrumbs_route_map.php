<?php
return [
    'admin/scripto' => [
        'breadcrumbs' => [],
        'text' => 'Dashboard', // @translate
        'params' => [],
    ],
    'admin/scripto-user' => [
        'breadcrumbs' => ['admin/scripto'],
        'text' => 'Users', // @translate
        'params' => [],
    ],
    'admin/scripto-user-contributions' => [
        'breadcrumbs' => ['admin/scripto', 'admin/scripto-user'],
        'text' => 'User contributions', // @translate
        'params' => ['user-id'],
    ],
    'admin/scripto-user-watchlist' => [
        'breadcrumbs' => ['admin/scripto', 'admin/scripto-user'],
        'text' => 'User watchlist', // @translate
        'params' => ['user-id'],
    ],
    'admin/scripto-project' => [
        'breadcrumbs' => ['admin/scripto'],
        'text' => 'Projects', // @translate
        'params' => [],
    ],
    'admin/scripto-item' => [
        'breadcrumbs' => ['admin/scripto', 'admin/scripto-project'],
        'text' => 'Review project', // @translate
        'params' => ['project-id'],
    ],
    'admin/scripto-media' => [
        'breadcrumbs' => ['admin/scripto', 'admin/scripto-project', 'admin/scripto-item'],
        'text' => 'Review item', // @translate
        'params' => ['project-id', 'item-id'],
    ],
    'admin/scripto-media-id' => [
        'breadcrumbs' => ['admin/scripto', 'admin/scripto-project', 'admin/scripto-item', 'admin/scripto-media'],
        'text' => 'Review media', // @translate
        'params' => ['project-id', 'item-id', 'media-id'],
    ],
    'admin/scripto-revision' => [
        'breadcrumbs' => ['admin/scripto', 'admin/scripto-project', 'admin/scripto-item', 'admin/scripto-media', 'admin/scripto-media-id'],
        'text' => 'Revisions', // @translate
        'params' => ['project-id', 'item-id', 'media-id'],
    ],
    'admin/scripto-revision-compare' => [
        'breadcrumbs' => ['admin/scripto', 'admin/scripto-project', 'admin/scripto-item', 'admin/scripto-media', 'admin/scripto-media-id', 'admin/scripto-revision'],
        'text' => 'Compare revisions', // @translate
        'params' => ['project-id', 'item-id', 'media-id', 'to-revision-id', 'from-revision-id'],
    ],
    'admin/scripto-talk-media-id' => [
        'breadcrumbs' => ['admin/scripto', 'admin/scripto-project', 'admin/scripto-item', 'admin/scripto-media', 'admin/scripto-media-id'],
        'text' => 'View notes', // @translate
        'params' => ['project-id', 'item-id', 'media-id'],
    ],
    'admin/scripto-talk-revision' => [
        'breadcrumbs' => ['admin/scripto', 'admin/scripto-project', 'admin/scripto-item', 'admin/scripto-media', 'admin/scripto-media-id', 'admin/scripto-talk-media-id'],
        'text' => 'Revisions', // @translate
        'params' => ['project-id', 'item-id', 'media-id'],
    ],
    'admin/scripto-talk-revision-compare' => [
        'breadcrumbs' => ['admin/scripto', 'admin/scripto-project', 'admin/scripto-item', 'admin/scripto-media', 'admin/scripto-media-id', 'admin/scripto-talk-media-id', 'admin/scripto-talk-revision'],
        'text' => 'Compare revisions', // @translate
        'params' => ['project-id', 'item-id', 'media-id', 'to-revision-id', 'from-revision-id'],
    ],
];
