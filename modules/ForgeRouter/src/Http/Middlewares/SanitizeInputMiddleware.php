<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Modules\ForgeRouter\Http\Middleware as MiddlewareImpl;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\Middleware;
use Modules\ForgeRouter\Security\InputSanitizer;

#[Middleware(group: 'global', order: 3, allowDuplicate: true, enabled: false)]
class SanitizeInputMiddleware extends MiddlewareImpl
{
    public function handle(Request $request, callable $next): Response
    {
        InputSanitizer::sanitizeRequest();
        return $next($request);
    }
}
