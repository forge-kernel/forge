<?php

return [
    'registry' => [
        [
            'name' => 'sftp-key',
            'type' => 'sftp',
            'host' => 'example.com',
            'port' => 22,
            'username' => env('SFTP_USER'),
            'key_path' => env('SFTP_KEY_PATH'),
            'key_passphrase' => env('SFTP_KEY_PASSPHRASE'),
            'base_path' => '/modules'
        ],
        [
            'name' => 'sftp-password',
            'type' => 'sftp',
            'host' => 'sftp.example.com',
            'port' => 22,
            'username' => env('SFTP_USER'),
            'password' => env('SFTP_PASS'),
            'base_path' => '/var/www/modules'
        ]
    ],
    'cache_ttl' => 3600
];

