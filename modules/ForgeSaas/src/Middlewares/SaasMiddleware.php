<?php

declare(strict_types=1);

namespace Modules\ForgeSaas\Middlewares;

use Modules\ForgeSaas\Contracts\SubscriptionManagerInterface;
use Modules\ForgeMultiTenant\DTO\Tenant;
use Forge\Core\DI\Container;
use Modules\ForgeRouter\Http\Middleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;

#[RegisterMiddleware(group: 'web', order: 5)]
final class SaasMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $tenant = $request->getAttribute('tenant');

        if ($tenant instanceof Tenant) {
            $container = Container::getInstance();
            $manager = $container->get(SubscriptionManagerInterface::class);
            $manager->forTenant($tenant);
        }

        return $next($request);
    }
}
