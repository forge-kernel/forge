<?php
declare(strict_types=1);

namespace Modules\ForgeWelcome\Common;

use Forge\Core\DI\Attributes\Injectable;
use Forge\Core\Module\Attributes\Provides;
use Modules\ForgeWelcome\Common\Contracts\WelcomeInterface;

#[Injectable]
#[Provides(interface: WelcomeInterface::class, version: '0.1.5')]
final class Welcome implements WelcomeInterface
{
    public function __construct(/** private AnotherServiceInterface $anotherService */)
    {

    }

    public function doSomething(): string
    {
        return "Doing something from the  Example module Service";
    }
}
