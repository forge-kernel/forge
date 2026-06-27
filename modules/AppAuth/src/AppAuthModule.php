<?php

declare(strict_types=1);

namespace App\Modules\AppAuth;

use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Structure;
use Forge\Core\DI\Container;
use App\Modules\ForgeAuth\Contracts\UserContextInterface;
use App\Modules\ForgeAuth\Contracts\UserProviderInterface;
use App\Modules\AppAuth\Repositories\UserRepository;
use App\Modules\AppAuth\Services\UserContext;

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


#[Service]
#[Module(name: 'AppAuth', version: '0.1.0', description: 'Application auth', order: 99, author: 'Your Name', license: 'MIT', tags: [])]
#[Compatibility(framework: '>=4.15.13', php: '>=8.3')]
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
