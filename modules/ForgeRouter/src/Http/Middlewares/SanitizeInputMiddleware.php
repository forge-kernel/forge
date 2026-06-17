<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use App\Modules\ForgeRouter\Security\InputSanitizer;

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
