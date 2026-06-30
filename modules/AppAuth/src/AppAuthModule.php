<?php

declare(strict_types=1);

namespace Modules\AppAuth;

use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Requires;
use Forge\Core\Module\Attributes\Structure;
use Forge\Core\DI\Container;
use Modules\ForgeAuth\Contracts\UserContextInterface;
use Modules\ForgeAuth\Contracts\UserProviderInterface;
use Modules\AppAuth\Repositories\UserRepository;
use Modules\AppAuth\Services\UserContext;

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
    'languages' => 'src/Languages',
])]

#[Module(name: 'AppAuth', version: '0.1.2', description: 'Application auth', order: 99, author: 'Your Name', license: 'MIT', tags: [])]
#[Compatibility(framework: '>=4.15.13', php: '>=8.3')]
#[Requires(module: "forge-database-sql")]
#[Requires(module: "forge-sql-orm")]
#[Requires(module: "forge-router")]
#[Requires(module: "forge-view")]
#[Requires(module: "forge-components")]
#[Requires(module: "forge-testing")]
#[Provides(interface: UserProviderInterface::class, version: "0.1.2")]
#[Provides(interface: UserContextInterface::class, version: "0.1.2")]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    "app_auth" => []
])]
#[PostInstall(command: 'app-auth:greet', args: [])]
#[PostUninstall(command: 'app-auth:greet', args: ['--post-uninstall'])]
final class AppAuthModule
{
    public function register(Container $container): void
    {
        $container->bind(UserProviderInterface::class, UserRepository::class);
        $container->bind(UserContextInterface::class, UserContext::class);
    }
}
