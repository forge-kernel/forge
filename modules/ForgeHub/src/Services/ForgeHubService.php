<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeHub\Contracts\ForgeHubInterface;

#[Service]
#[Provides(interface: ForgeHubInterface::class, version: '0.1.0')]
#[Requires]
final class ForgeHubService implements ForgeHubInterface
{
    public function __construct(/** private AnotherServiceInterface $anotherService */)
    {
    }
    public function doSomething(): string
    {
        return "Doing something from the  Example module Service";
    }
}
