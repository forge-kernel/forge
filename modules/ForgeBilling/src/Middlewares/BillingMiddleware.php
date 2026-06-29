<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Middlewares;

use Modules\ForgeBilling\Services\BillingSubscriptionService;
use Modules\ForgeMultiTenant\DTO\Tenant;
use Forge\Core\DI\Container;
use Modules\ForgeRouter\Http\Middleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;

#[RegisterMiddleware(group: 'web', order: 7)]
final class BillingMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $tenant = $request->getAttribute('tenant');

        if ($tenant instanceof Tenant) {
            $container = Container::getInstance();
            $subscriptionService = $container->get(BillingSubscriptionService::class);
            $subscriptionService->forTenant($tenant->id);
        }

        return $next($request);
    }
}
