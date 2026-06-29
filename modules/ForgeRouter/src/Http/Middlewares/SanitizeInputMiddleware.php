<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\DI\Attributes\Service;
use Modules\ForgeRouter\Http\Middleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use Modules\ForgeRouter\Security\InputSanitizer;

#[Service]
#[RegisterMiddleware(group: 'global', order: 3, allowDuplicate: true, enabled: false)]
class SanitizeInputMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        InputSanitizer::sanitizeRequest();
        return $next($request);
    }
}
