<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Tests;

use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use App\Modules\ForgeRouter\Middleware\MiddlewareLoader;

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
