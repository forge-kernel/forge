<?php
declare(strict_types=1);

namespace App\Modules\ForgeAppAuth;

use App\Modules\ForgeAppAuth\Repositories\UserRepository;
use App\Modules\ForgeAppAuth\Services\UserContext;
use App\Modules\ForgeAuth\Contracts\UserContextInterface;
use App\Modules\ForgeAuth\Contracts\UserProviderInterface;
use Forge\Core\Module\Attributes\Requires;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Structure;

#[Structure(structure: [
    'controllers' => 'src/Controllers',
    'services' => 'src/Services',
    'migrations' => 'src/Database/Migrations',
    'views' => 'src/UI/views',
    'components' => 'src/UI/views/components',
    'commands' => 'src/Commands',
    'events' => 'src/Events',
    'tests' => 'src/tests',
    'models' => 'src/Models',
    'dto' => 'src/Dto',
    'seeders' => 'src/Database/Seeders',
    'middlewares' => 'src/Middlewares',
])]
#[Module(
    name: 'ForgeAppAuth',
    version: '0.1.2',
    description: 'Distributable authentication implementation with login, register, forgot-password, and reset-password',
    order: 60,
    author: 'Forge Team',
    license: 'MIT',
    type: 'generic',
    tags: ['auth', 'authentication', 'login', 'register', 'password-reset'],
)]
#[Requires(module: "ForgeAuth", version: ">=2.0.5")]
#[Compatibility(framework: '>=4.15.13', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    "forge_app_auth" => [
        "password_reset" => [
            "token_ttl" => 3600,
            "redirect_after_reset" => "/auth/login",
        ],
    ],
])]
#[PostInstall(command: 'db:migrate', args: ['--type=module', '--module=ForgeAppAuth'])]
#[PostUninstall(command: 'db:migrate:rollback', args: ['--type=module', '--module=ForgeAppAuth'])]
final class ForgeAppAuthModule
{
    public function register(Container $container): void
    {
        $container->bind(UserProviderInterface::class, UserRepository::class);
        $container->bind(UserContextInterface::class, UserContext::class);
    }
}
