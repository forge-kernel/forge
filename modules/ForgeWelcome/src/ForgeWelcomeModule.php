<?php

declare(strict_types=1);

namespace App\Modules\ForgeWelcome;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeWelcome\Contracts\ForgeWelcomeInterface;
use App\Modules\ForgeWelcome\Services\ForgeWelcomeService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\Structure;

#[Module(
    name: 'ForgeWelcome',
    version: '1.2.2',
    description: 'A playground by forge',
    order: 99,
    author: 'Forge Team',
    license: 'MIT',
    type: 'generic',
    tags: ['generic', 'welcome', 'playground', 'forge']
)]
#[Service]
#[Compatibility(framework: '>=4.15.10', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
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
#[PostInstall(command: 'asset:link', args: ['--type=module', '--module=forge-welcome'])]
#[PostUninstall(command: 'asset:unlink', args: ['--type=module', '--module=forge-welcome'])]
final class ForgeWelcomeModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $container->bind(ForgeWelcomeInterface::class, ForgeWelcomeService::class);
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
    public function onAfterModuleRegister(): void
    {
        //error_log("ForgeWelcome:  registered!");
    }
}
