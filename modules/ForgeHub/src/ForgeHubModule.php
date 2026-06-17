<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeHub\Contracts\ForgeHubInterface;
use App\Modules\ForgeHub\Services\ForgeHubService;
use App\Modules\ForgeHub\Services\HubItemRegistry;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\HubItem;
use Forge\Core\Module\ForgeIcon;
use Forge\Core\Security\PermissionsEnum;

#[Module(
    name: 'ForgeHub',
    version: '2.5.4',
    description: 'Administration Hub for Forge Framework',
    order: 6,
    author: 'Forge Team',
    license: 'MIT',
    type: 'generic',
    tags: ['generic', 'hub', 'management', 'system', 'administration-hub']
)]
#[HubItem(label: 'CLI Command', route: '/hub/commands', icon: ForgeIcon::COMMAND, order: 4)]
#[HubItem(label: 'Logs', route: '/hub/logs', icon: ForgeIcon::LOG, order: 3)]
#[HubItem(label: 'Modules', route: '/hub/modules', icon: ForgeIcon::STORAGE, order: 2)]
#[HubItem(label: 'Cache', route: '/hub/cache', icon: ForgeIcon::CACHE, order: 5)]
#[HubItem(label: 'Cron Jobs', route: '/hub/cron-jobs', icon: ForgeIcon::CLOCK, order: 8)]
#[HubItem(label: 'Monitoring', route: '/hub/monitoring', icon: ForgeIcon::MONITOR, order: 9)]
#[Service]
#[Compatibility(framework: '>=4.15.10', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
final class ForgeHubModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $container->bind(ForgeHubInterface::class, ForgeHubService::class);

        if ($container->has(HubItemRegistry::class)) {
            $registry = $container->get(HubItemRegistry::class);
            $registry->refresh();
        }
    }
}
