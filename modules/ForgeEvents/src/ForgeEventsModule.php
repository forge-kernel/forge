<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\HubItem;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\ForgeIcon;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(
    name: "ForgeEvents",
    version: "1.4.3",
    description: "An Event Queue system by forge",
    order: 99,
    author: 'Forge Team',
    license: 'MIT',
    type: 'communication',
    tags: ['communication', 'event', 'queue', 'event-queue', 'event-dispatcher', 'event-listener']
)]
#[HubItem(label: 'Queues', route: '/hub/queues', icon: ForgeIcon::QUEUE, order: 7)]
#[HubItem(label: 'Queue Workers', route: '/hub/queue-workers', icon: ForgeIcon::COMMAND, order: 8)]
#[Service]
#[Compatibility(framework: ">=0.1.0", php: ">=8.3")]
#[Repository(type: "git", url: "https://github.com/forge-kernel/kernel-module-registry")]
final class ForgeEventsModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
    }
}
