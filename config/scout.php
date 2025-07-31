<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search connection that gets used while
    | using Laravel Scout. This connection is used when syncing all models
    | to the search service. You should adjust this based on your needs.
    |
    | Supported: "algolia", "meilisearch", "database", "collection", "null"
    |
    */

    'driver' => env('SCOUT_DRIVER', 'typesense'),


    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you are hosting
    | multiple applications of the same name, as is recommended with
    | Elasticsearch.
    |
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing will get queued for better performance.
    |
    */

    'queue' => env('SCOUT_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Batch Size
    |--------------------------------------------------------------------------
    |
    | This option allows you to control the amount of records that are imported
    | to the search index in batch. You can use this to fine tune the memory
    | usage vs search speed. You can change it as needed.
    |
    */

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option allows to control whether to keep soft deleted records in
    | the search indexes. Maintaining soft deleted records can be useful
    | if your application still needs to search for the records later.
    |
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify
    |--------------------------------------------------------------------------
    |
    | This option allows you to control the action the engine takes when
    | an "identify" command is executed. The default action will first
    | be to "search" the records, and then "updateOrCreate" them in
    | the index. You can change it to "skip" to not perform any
    | action, or "updateOrCreate" to perform the "updateOrCreate"
    | action immediately, without first searching the records.
    |
    */

    'identify' => false,

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Algolia settings. Algolia is a cloud hosted
    | search engine which works great with Scout out of the box. Just plug
    | in your application ID and admin API key to get started searching.
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
    ],


    /*
    |--------------------------------------------------------------------------
    | Typesense Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Typesense settings. Typesense is an open source
    | search engine with minimal configuration. Below, you can just plug in your
    | host address to get started searching.
    |
    */

    'typesense' => [
        'client-settings' => [
            'api_key' => env('TYPESENSE_API_KEY', ''),
            'nodes'=> [
                [
                    'host' => env('TYPESENSE_HOST', 'localhost'),
                    'port' => env('TYPESENSE_PORT', 8108),
                    'path' => env('TYPESENSE_PATH', ''),
                    'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
                ]
            ],
            'nearest_nodes' => [
                [
                    'host' => env('TYPESENSE_HOST', 'localhost'),
                    'port' => env('TYPESENSE_PORT', 8108),
                    'path' => env('TYPESENSE_PATH', ''),
                    'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
                ]
            ],
            'connection_timeout_seconds' => env('TYPESENSE_CONNECTION_TIMEOUT_SECONDS', 2),
            'healthcheck_interval_seconds' => env('TYPESENSE_HEALTHCHECK_INTERVAL_SECONDS', 30),
            'num_retries' => env('TYPESENSE_RETRIES', 3),
            'retry_interval_seconds' => env('TYPESENSE_RETRY_INTERVAL_SECONDS', 1),
        ],
        'model-settings' => [
            \App\Models\Protocol::class => [
                'collection-schema' => [
                    'fields' => [
                        [
                            'name' => 'id',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'title',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'content',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'tags',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'author',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'rating',
                            'type' => 'float',
                        ],
                        [
                            'name' => 'reviews_count',
                            'type' => 'int32',
                        ],
                        [
                            'name' => 'created_at', 
                            'type' => 'int64',
                        ]
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title, content, tags, author',
                    'filter_by' => 'rating, reviews_count',
                    'sort_by' => 'created_at:desc',
                ],
            ],
            \App\Models\Thread::class => [
                'collection-schema' => [
                    'fields' => [
                        [
                            'name' => 'id',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'title',
                            'type'=> 'string',  
                        ],
                        [
                            'name' => 'body',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'author',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'protocol_id',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'vote_score',
                            'type' => 'int32',
                        ],
                        [
                            'name' => 'created_at',
                            'type' => 'int64',
                        ],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title, body, author',
                    'filter_by' => 'protocol_id',
                    'sort_by' => 'created_at:desc',
                ],
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Meilisearch settings. Meilisearch is an
    | open source search engine with minimal configuration. Below, you can
    | just plug in your host address to get started searching.
    |
    | You can find your host URL and key within your Meilisearch admin
    | panel located in your project's dashboard.
    |
    */

    // 'meilisearch' => [
    //     'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
    //     'key' => env('MEILISEARCH_KEY', null),
    //     'index-settings' => [
    //         \App\Models\Protocol::class => [
    //             'filterableAttributes' => ['title', 'content', 'tags', 'author', 'rating'],
    //             'sortableAttributes' => ['created_at', 'rating', 'reviews_count'],
    //         ],
    //         \App\Models\Thread::class => [
    //             'filterableAttributes' => ['protocol_id', 'title', 'author', 'body'],
    //             'sortableAttributes' => ['created_at', 'vote_score'],
    //         ],
    //     ],
    // ],

]; 