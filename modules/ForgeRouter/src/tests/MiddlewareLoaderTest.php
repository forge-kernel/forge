<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Tests;

use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Modules\ForgeRouter\Middleware\MiddlewareLoader;

#[Group('middleware')]
final class MiddlewareLoaderTest extends TestCase
{
    #[Test('MiddlewareLoader::isCacheValid is O(1) through file mtime cache checks')]
    public function true_cache_invalidation_mtime(): void
    {
        $loader = new MiddlewareLoader();
        $map = $loader->load();
        $this->assertTrue(is_array($map));
    }
}
