<?php

declare(strict_types=1);

namespace App\Modules\ForgeView;

use Forge\Core\Contracts\ViewInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;

#[Module(
    name: 'ForgeView',
    version: '0.1.1',
    description: 'A View engine provided by forge',
    order: 4,
    author: 'Forge Team',
    license: 'MIT',
    type: 'core',
    tags: ['view-engine', 'view'])]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Provides(interface: ViewInterface::class, version: '0.1.1')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]

final class ForgeViewModule
{
    public function register(Container $container): void
    {
        $container->singleton(ViewInterface::class, View::class);
    }
}
