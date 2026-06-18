<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth;

use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeAuth\Contracts\ForgeAuthInterface;
use App\Modules\ForgeAuth\Contracts\UserProviderInterface;
use App\Modules\ForgeAuth\Services\ForgeAuthService;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\Structure;

#[Service]
#[Module(
    name: 'ForgeAuth',
    version: '2.0.2',
    description: 'An Auth module by forge.',
    order: 99,
    author: 'Forge Team',
    license: 'MIT',
    type: 'auth',
    tags: ['auth', 'authentication', 'authorization', 'authentication-system', 'authentication-library', 'authentication-framework']
)]
#[Compatibility(framework: '>=4.15.10', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    'forge_auth' => [
        'jwt' => [
            'enabled' => false,
            'secret' => 'your-secure-jwt-secret',
            'ttl' => 900,
            'refresh_ttl' => 604800,
        ],
        'password' => [
            'password_cost' => 12,
            'max_login_attempts' => 3,
            'lockout_time' => 300,
            'min_password_length' => 6,
            'max_password_length' => 256,
        ],
        'auth' => [
            'redirect' => [
                'after_login' => '/',
                'after_logout' => '/',
            ],
        ],
    ],
])]
#[Structure(structure: [
    'controllers' => 'src/Controllers',
    'services' => 'src/Services',
    'migrations' => 'src/Database/Migrations',
    'views' => 'src/Resources/views',
    'components' => 'src/Resources/components',
    'commands' => 'src/Commands',
    'events' => 'src/Events',
    'tests' => 'src/tests',
    'models' => 'src/Models',
    'dto' => 'src/Dto',
    'seeders' => 'src/Database/Seeders',
    'middlewares' => 'src/Middlewares',
])]
#[PostInstall(command: 'db:migrate', args: ['--type=module', '--module=ForgeAuth'])]
#[PostUninstall(command: 'db:migrate', args: ['--type=module', '--module=ForgeAuth'])]
final class ForgeAuthModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $this->setupConfigDefaults($container);
        $container->bind(ForgeAuthInterface::class, ForgeAuthService::class);
    }

    private function setupConfigDefaults(Container $container): void
    {
        /** @var Config $config */
        $config = $container->get(Config::class);
        $config->set('forge_auth.jwt.enabled', env('FORGE_JWT_ENABLED', false));
        $config->set('forge_auth.jwt.secret', env('FORGE_JWT_SECRET', 'your-secure-jwt-secret'));
        $config->set('forge_auth.jwt.ttl', env('FORGE_JWT_TTL', 900));
        $config->set('forge_auth.jwt.refresh_ttl', env('FORGE_JWT_REFRESH_TTL', 604800));
        $config->set('forge_auth.password.password_cost', env('FORGE_PASSWORD_COST', 12));
        $config->set('forge_auth.password.max_login_attempts', env('FORGE_MAX_LOGIN_ATTEMPTS', 3));
        $config->set('forge_auth.password.lockout_time', env('FORGE_LOCKOUT_TIME', 300));
        $config->set('forge_auth.password.min_password_length', env('FORGE_MIN_PASSWORD_LENGTH', 6));
        $config->set('forge_auth.password.max_password_length', env('FORGE_MAX_PASSWORD_LENGTH', 256));
        $config->set('forge_auth.auth.redirect.after_login', env('FORGE_AFTER_LOGIN_REDIRECT', '/'));
        $config->set('forge_auth.auth.redirect.after_logout', env('FORGE_AFTER_LOGOUT_REDIRECT', '/'));
    }
}
