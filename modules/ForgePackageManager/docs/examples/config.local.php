<?php

return [
    'registry' => [
        [
            'name' => 'local-filesystem',
            'type' => 'local',
            'path' => '/path/to/modules-registry'
        ],
        [
            'name' => 'local-relative',
            'type' => 'local',
            'path' => 'storage/modules-registry'
        ],
        [
            'name' => 'dev-modules',
            'type' => 'local',
            'path' => '/home/developer/modules'
        ]
    ],
    'cache_ttl' => 3600
];

