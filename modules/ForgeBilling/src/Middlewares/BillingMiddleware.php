<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Middlewares;

use App\Modules\ForgeBilling\Services\BillingSubscriptionService;
use App\Modules\ForgeMultiTenant\DTO\Tenant;
use Forge\Core\DI\Container;
use App\Modules\ForgeRouter\Http\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;

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
