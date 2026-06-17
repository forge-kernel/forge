<?php

return [
    'registry' => [
        [
            'name' => 'network-mount-linux',
            'type' => 'network',
            'path' => '/mnt/modules'
        ],
        [
            'name' => 'network-mount-macos',
            'type' => 'network',
            'path' => '/Volumes/modules'
        ],
        [
            'name' => 'windows-unc',
            'type' => 'network',
            'path' => '\\\\server\\share\\modules'
        ],
        [
            'name' => 'smb-url',
            'type' => 'network',
            'path' => 'smb://server/share/modules'
        ]
    ],
    'cache_ttl' => 3600
];

