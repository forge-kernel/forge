<?php

declare(strict_types=1);

namespace Modules\ForgeView;

use Forge\Core\Contracts\ViewInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\ResetManager;
use Modules\ForgeView\ViewState;

#[Module(
    name: 'ForgeView',
    version: '0.1.8',
    description: 'A View engine provided by forge',
    order: 4,
    author: 'Forge Team',
    license: 'MIT',
    type: 'core',
    tags: ['view-engine', 'view'])]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Provides(interface: ViewInterface::class, version: '0.1.8')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]

final class ForgeViewModule
{
    public function register(Container $container): void
    {
        $container->singleton(ViewInterface::class, View::class);

        ResetManager::onBefore([ViewState::class, 'reset']);
    }
}
