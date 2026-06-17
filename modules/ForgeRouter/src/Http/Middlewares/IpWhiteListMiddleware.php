<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use App\Modules\ForgeRouter\Traits\ResponseHelper;

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
        $clientIp = $request->getClientIp();

        if (!in_array($clientIp, $allowedIps, true)) {
            return $this->createErrorResponse($request, 'Forbidden', 403);
        }

        return $next($request);
    }
}
