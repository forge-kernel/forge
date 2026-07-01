<?php
return [
    'csp' => [
        'enabled' => env('CSP_ENABLED', false),
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
        ],
    ],
];
