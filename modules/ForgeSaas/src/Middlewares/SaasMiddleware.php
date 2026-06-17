<?php

declare(strict_types=1);

namespace App\Modules\ForgeSaas\Middlewares;

use App\Modules\ForgeSaas\Contracts\SubscriptionManagerInterface;
use App\Modules\ForgeMultiTenant\DTO\Tenant;
use Forge\Core\DI\Container;
use App\Modules\ForgeRouter\Http\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;

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
