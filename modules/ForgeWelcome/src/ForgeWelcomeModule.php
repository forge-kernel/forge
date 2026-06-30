<?php

declare(strict_types=1);

namespace Modules\ForgeWelcome;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Modules\ForgeWelcome\Common\Contracts\WelcomeInterface;
use Modules\ForgeWelcome\Common\Welcome;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\CLI\Traits\OutputHelper;

#[Module(
    name: 'ForgeWelcome',
    version: '1.2.6',
    description: 'A playground by forge',
    order: 99,
    author: 'Forge Team',
    license: 'MIT',
    type: 'generic',
    tags: ['generic', 'welcome', 'playground', 'forge']
)]
#[Compatibility(framework: '>=4.15.10', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[PostInstall(command: 'asset:link', args: ['--type=module', '--module=forge-welcome'])]
#[PostUninstall(command: 'asset:unlink', args: ['--type=module', '--module=forge-welcome'])]
final class ForgeWelcomeModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $container->bind(WelcomeInterface::class, Welcome::class);
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
    public function onAfterModuleRegister(): void
    {
        //error_log("ForgeWelcome:  registered!");
    }
}
