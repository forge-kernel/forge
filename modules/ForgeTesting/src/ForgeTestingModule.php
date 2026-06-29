<?php

declare(strict_types=1);

namespace Modules\ForgeTesting;

use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;

#[Module(
    name: "ForgeTesting",
    version: "0.4.3",
    description: "A Test Suite Module By Forge",
    order: 9999,
    isCli: true,
    author: 'Forge Team',
    license: 'MIT',
    type: 'testing',
    tags: ['testing', 'unit', 'integration']
)]
#[Compatibility(framework: ">=0.1.20", php: ">=8.3")]
#[Repository(type: "git", url: "https://github.com/forge-kernel/kernel-module-registry")]
final class ForgeTestingModule
{
}
