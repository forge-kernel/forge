<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Attributes;

use Attribute;

#[Attribute(
    Attribute::TARGET_CLASS |
    Attribute::TARGET_METHOD |
    Attribute::IS_REPEATABLE)]
class UseMiddleware
{
    public array $middleware;

    public function __construct(string|array ...$middlewares)
    {
        $this->middleware = [];
        foreach ($middlewares as $mw) {
            if (is_array($mw)) {
                $this->middleware = array_merge($this->middleware, $mw);
            } else {
                $this->middleware[] = $mw;
            }
        }
    }
}
