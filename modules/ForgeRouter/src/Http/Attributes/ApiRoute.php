<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Attributes;

use Attribute;
use Modules\ForgeRouter\Routing\Endpoint;

#[Attribute(Attribute::TARGET_METHOD)]
final class ApiRoute extends Endpoint
{
    public function __construct(
        string $path,
        string $method = 'GET',
        array $middleware = [],
        string $prefix = 'api',
        string $version = 'v1',
        array $permissions = [],
        bool $override = false,
    ) {
        $apiPath = "/{$prefix}/{$version}{$path}";
        parent::__construct($apiPath, $method, $middleware, $permissions, $override);
    }
}
