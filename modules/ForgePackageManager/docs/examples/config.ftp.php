<?php

return [
    'registry' => [
        [
            'name' => 'ftp-basic',
            'type' => 'ftp',
            'host' => env('FTP_HOST'),
            'port' => 21,
            'username' => env('FTP_USER'),
            'password' => env('FTP_PASS'),
            'base_path' => '/modules',
            'passive' => true,
            'ssl' => false
        ],
        [
            'name' => 'ftps-explicit',
            'type' => 'ftp',
            'host' => env('FTP_HOST'),
            'port' => 21,
            'username' => env('FTP_USER'),
            'password' => env('FTP_PASS'),
            'base_path' => '/modules',
            'passive' => true,
            'ssl' => true
        ],
        [
            'name' => 'ftps-implicit',
            'type' => 'ftp',
            'host' => env('FTP_HOST'),
            'port' => 990,
            'username' => env('FTP_USER'),
            'password' => env('FTP_PASS'),
            'base_path' => '/modules',
            'passive' => true,
            'ssl' => true
        ]
    ],
    'cache_ttl' => 3600
];

