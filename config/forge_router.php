<?php
return [
    'csp' => [
        'enabled' => filter_var(env('CSP_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'directives' => filter_var(env('CSP_ENABLED', false), FILTER_VALIDATE_BOOLEAN) ? [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
        ] : [],
    ],
];
