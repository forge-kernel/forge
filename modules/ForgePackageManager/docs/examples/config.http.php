<?php

return [
    'registry' => [
        [
            'name' => 'http-public',
            'type' => 'http',
            'base_url' => 'https://example.com/modules'
        ],
        [
            'name' => 'http-auth',
            'type' => 'http',
            'base_url' => 'https://example.com/modules',
            'username' => env('HTTP_USER'),
            'password' => env('HTTP_PASS')
        ],
        [
            'name' => 'http-timeout',
            'type' => 'http',
            'base_url' => 'https://example.com/modules',
            'timeout' => 60
        ],
        [
            'name' => 'cdn-modules',
            'type' => 'http',
            'base_url' => 'https://cdn.example.com/modules'
        ]
    ],
    'cache_ttl' => 3600
];

