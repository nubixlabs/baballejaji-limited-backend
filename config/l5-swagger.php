<?php

return [
    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Baballe Jaji Limited API',
            ],

            'routes' => [
                'api' => 'api/documentation',
                'middleware' => [
                    'api',
                ],
            ],

            'paths' => [
                'docs' => storage_path('api-docs'),
                'docs_json' => 'api-docs.json',
                'annotations' => [
                    base_path('app')
                ],
            ],
        ],
    ],
];


