<?php

declare(strict_types=1);

namespace Modules\ForgeHub;

use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Modules\ForgeHub\Services\HubItemRegistry;
use Modules\ForgeHub\Services\ObservabilityService;
use Modules\ForgeHub\Services\ObservabilityServiceInterface;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\HubItem;
use Forge\Core\Module\ForgeIcon;
use Forge\Core\Security\PermissionsEnum;

#[Module(
    name: 'ForgeHub',
    version: '2.5.12',
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
#[HubItem(label: 'Queues', route: '/hub/queues', icon: ForgeIcon::QUEUE, order: 6)]
#[HubItem(label: 'Queue Workers', route: '/hub/queue-workers', icon: ForgeIcon::COMMAND, order: 7)]
#[HubItem(label: 'Cron Jobs', route: '/hub/cron-jobs', icon: ForgeIcon::CLOCK, order: 8)]
#[HubItem(label: 'Monitoring', route: '/hub/monitoring', icon: ForgeIcon::MONITOR, order: 9)]
#[HubItem(label: 'Observability', route: '/hub/observability', icon: ForgeIcon::MONITOR, order: 10)]
#[HubItem(label: 'Deployment', route: '/hub/deployment', icon: ForgeIcon::DEPLOY, order: 10)]
#[Compatibility(framework: '>=4.15.10', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    'forge_observability' => [
        'enabled' => false,
        'sampling' => [
            'strategy' => 'adaptive',
            'base_rate' => 0.1,
            'slow_threshold_ms' => 200,
            'slow_query_ms' => 100,
        ],
        'storage' => [
            'retention_days' => 7,
        ],
    ],
])]
final class ForgeHubModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $this->setupConfigDefaults($container);
        $container->bind(ObservabilityServiceInterface::class, ObservabilityService::class);

        if ($container->has(HubItemRegistry::class)) {
            $registry = $container->get(HubItemRegistry::class);
            $registry->refresh();
        }
    }

    private function setupConfigDefaults(Container $container): void
    {
        $config = $container->get(Config::class);
        $config->set('forge_observability.enabled', filter_var(env('APP_METRICS_ENABLED', env('FORGE_OBSERVABILITY_ENABLED', false)), FILTER_VALIDATE_BOOLEAN));
    }
}
