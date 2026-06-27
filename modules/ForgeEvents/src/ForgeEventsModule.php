<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents;

use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;

#[Module(
    name: "ForgeEvents",
    version: "1.4.5",
    description: "An Event Queue system by forge",
    order: 99,
    author: 'Forge Team',
    license: 'MIT',
    type: 'communication',
    tags: ['communication', 'event', 'queue', 'event-queue', 'event-dispatcher', 'event-listener']
)]
#[Compatibility(framework: ">=0.1.0", php: ">=8.3")]
#[Repository(type: "git", url: "https://github.com/forge-kernel/kernel-module-registry")]
final class ForgeEventsModule
{
}
