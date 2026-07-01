<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Modules\ForgeRouter\Http\Middleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use Modules\ForgeRouter\Traits\ResponseHelper;

#[Service]
#[RegisterMiddleware(group: 'api', order: 0, allowDuplicate: true, enabled: true)]
class IpWhiteListMiddleware extends Middleware
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
