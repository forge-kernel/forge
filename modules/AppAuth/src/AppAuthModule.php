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
use Forge\Core\Module\Traits\IncludesFiles;
use Forge\Core\DI\Container;
use Modules\ForgeAuth\Contracts\UserContextInterface;
use Modules\ForgeAuth\Contracts\UserProviderInterface;
use Modules\AppAuth\Repositories\UserRepository;
use Modules\AppAuth\Services\UserContext;

#[Structure(structure: [
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

#[Module(name: 'AppAuth', version: '0.1.6', description: 'Application auth', order: 99, author: 'Your Name', license: 'MIT', tags: [])]
#[Compatibility(framework: '>=4.15.13', php: '>=8.3')]
#[Requires(module: "forge-database-sql")]
#[Requires(module: "forge-sql-orm")]
#[Requires(module: "forge-router")]
#[Requires(module: "forge-view")]
#[Requires(module: "forge-components")]
#[Requires(module: "forge-testing")]
#[Provides(interface: UserProviderInterface::class, version: "0.1.6")]
#[Provides(interface: UserContextInterface::class, version: "0.1.6")]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    "app_auth" => []
])]
#[PostInstall(command: 'db:migrate', args: ['--type=module', '--module=app-auth'])]
#[PostUninstall(command: 'db:migrate:rollback', args: ['--type=module', '--module=app-auth'])]
final class AppAuthModule
{
    use IncludesFiles;

    protected function includes(): array
    {
        return [
            __DIR__ . '/Support/helpers.php',
        ];
    }

    public function register(Container $container): void
    {
        $container->bind(UserProviderInterface::class, UserRepository::class);
        $container->bind(UserContextInterface::class, UserContext::class);
    }
}
