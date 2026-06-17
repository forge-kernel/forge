<?php

declare(strict_types=1);

namespace App\Modules\ForgeTesting;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;

#[Module(
    name: "ForgeTesting",
    version: "0.4.0",
    description: "A Test Suite Module By Forge",
    order: 9999,
    isCli: true,
    author: 'Forge Team',
    license: 'MIT',
    type: 'testing',
    tags: ['testing', 'unit', 'integration']
)]
#[Service]
#[Compatibility(framework: ">=0.1.20", php: ">=8.3")]
#[Repository(type: "git", url: "https://github.com/forge-kernel/kernel-module-registry")]
#[Provides(interface: TestCase::class, version: "0.4.0")]
final class ForgeTestingModule
{
    public function register(Container $container): void
    {
    }
}
