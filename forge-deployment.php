<?php

declare(strict_types=1);

return [
    'php_executable' => '/Users/acidlake/Library/Application Support/Herd/bin/php84',
    'server' => [
        'name' => 'forge-kernel-demo',
        'region' => 'nyc1',
        'size' => 's-1vcpu-1gb',
        'image' => 'ubuntu-22-04-x64',
        'ssh_key_path' => null,
    ],
    'provision' => [
        'php_version' => '8.4',
        'database_type' => 'sqlite',
        'database_version' => '8',
        'database_name' => 'forge_app',
        'database_user' => 'forge_user',
        'database_password' => 'secret',
    ],
    'deployment' => [
        'domain' => 'kernel.upper.do',
        'ssl_email' => 'jeremias2@gmail.com',
        'commands' => [],
        'post_deployment_commands' => [
            'cache:flush',
            'cache:warm',
            'db:migrate --type=all',
            'storage:link',
            'modules:forge-deployment:fix-permissions',
            'modules:forgewire:minify',
            'asset:link --type=module --module=forge-wire',
            'asset:link --type=module --module=forge-debug-bar',
        ],
        'env_vars' => [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'true',
            'CENTRAL_DOMAIN' => 'kernel.upper.do',
            'CORS_ALLOWED_ORIGINS' => [
                'https://forge.upper.do',
            ],
            'IP_WHITE_LIST' => [
                '127.0.0.1',
                '::1',
            ],
            'FORGE_WIRE_USE_MINIFIED' => 'true',
            'FORGE_WIRE_STALE_THRESHOLD' => 300,
            'FORGE_DEBUG_BAR_ENABLED' => 'false',
            'DISABLED_MODULES' => [
                'ForgeMultiTenant',
                'ForgeWelcome',
            ],
        ],
    ],
];