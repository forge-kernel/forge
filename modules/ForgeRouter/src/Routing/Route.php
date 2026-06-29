<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Routing;

use Attribute;

/**
 * @deprecated Use #[Endpoint] instead
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route extends Endpoint
{
    public function __construct(
        string $path = '',
        string $method = "GET",
        array $middleware = [],
        array $permissions = [],
        bool $override = false,
    ) {
        parent::__construct($path, $method, $middleware, $permissions, $override);
    }
}
