<?php
declare(strict_types=1);

namespace App\Modules\ForgeWelcome\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeWelcome\Contracts\ForgeWelcomeInterface;

#[Service]
#[Provides(interface: ForgeWelcomeInterface::class, version: '0.1.5')]
#[Requires]
final class ForgeWelcomeService implements ForgeWelcomeInterface
{
    public function __construct(/** private AnotherServiceInterface $anotherService */)
    {

    }

    public function doSomething(): string
    {
        return "Doing something from the  Example module Service";
    }
}