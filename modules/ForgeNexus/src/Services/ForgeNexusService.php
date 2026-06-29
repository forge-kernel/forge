<?php

declare(strict_types=1);

namespace Modules\ForgeNexus\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use Modules\ForgeNexus\Contracts\ForgeNexusInterface;

#[Service]
#[Provides(interface: ForgeNexusInterface::class, version: '0.1.0')]
#[Requires]
final class ForgeNexusService implements ForgeNexusInterface
{
    public function __construct()
    {
    }
    public function doSomething(): string
    {
        return "Doing something from the  Example module Service";
    }
}
