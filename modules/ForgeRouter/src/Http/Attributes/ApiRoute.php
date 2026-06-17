<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http\Attributes;

use Attribute;
use App\Modules\ForgeRouter\Routing\Route;

#[Attribute(Attribute::TARGET_METHOD)]
final class ApiRoute extends Route
{
    public function __construct(
        string $path,
        string $method = 'GET',
        public array $middleware = [],
        string $prefix = 'api',
        string $version = 'v1',
        public array $permissions = []
    ) {
        $apiPath = "/{$prefix}/{$version}{$path}";
        parent::__construct($apiPath, $method, $middleware, $permissions);
    }
}
