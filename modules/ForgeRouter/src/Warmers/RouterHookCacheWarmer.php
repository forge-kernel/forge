<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Warmers;

use Modules\ForgeRouter\Events\RouterHookManager;
use Forge\Core\Contracts\Cache\CacheWarmerInterface;
use Forge\Core\DI\Attributes\Injectable;

#[Injectable]
final class RouterHookCacheWarmer implements CacheWarmerInterface
{
    public function warmCache(): void
    {
        RouterHookManager::rebuild();
    }
}
