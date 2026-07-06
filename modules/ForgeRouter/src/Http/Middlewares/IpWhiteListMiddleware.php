<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\Config\Config;
use Modules\ForgeRouter\Http\Middleware as MiddlewareImpl;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\Middleware;
use Modules\ForgeRouter\Traits\ResponseHelper;

#[Middleware(group: 'api', order: 0, allowDuplicate: true, enabled: true)]
class IpWhiteListMiddleware extends MiddlewareImpl
{
    use ResponseHelper;

    public function __construct(private readonly Config $config)
    {
    }
    public function handle(Request $request, callable $next): Response
    {
        $allowedIps = $this->config->get('forge_router.ip_whitelist');

        if (!empty($allowedIps)) {
            $clientIp = $request->getClientIp();
            if (!in_array($clientIp, $allowedIps, true)) {
                return $this->createErrorResponse($request, 'Forbidden', 403);
            }
        }

        return $next($request);
    }
}
