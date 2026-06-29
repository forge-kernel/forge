<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Warmers;

use App\Modules\ForgeRouter\Middleware\MiddlewareLoader;
use Forge\Core\Contracts\Cache\CacheWarmerInterface;
use Forge\Core\DI\Attributes\Injectable;
use Forge\Core\DI\Container;
use Forge\Core\Structure\StructureResolver;

#[Injectable]
final class MiddlewareMapCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function warmCache(): void
    {
        $structureResolver = $this->container->has(StructureResolver::class)
            ? $this->container->get(StructureResolver::class)
            : null;

        $middlewareLoader = new MiddlewareLoader($structureResolver);
        $middlewareLoader->load();
    }
}
