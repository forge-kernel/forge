<?php

declare(strict_types=1);

namespace Modules\ForgeSaas\Middlewares;

use Modules\ForgeSaas\Attributes\RequiresFeature;
use Modules\ForgeSaas\Attributes\RequiresPlan;
use Modules\ForgeSaas\Attributes\WithinLimit;
use Modules\ForgeSaas\Contracts\SubscriptionManagerInterface;
use Modules\ForgeMultiTenant\DTO\Tenant;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;
use Modules\ForgeRouter\Http\Middleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Traits\ResponseHelper;
use ReflectionClass;
use ReflectionMethod;

final class FeatureGateMiddleware extends Middleware
{
    use ResponseHelper;

    public function handle(Request $request, callable $next): Response
    {
        $tenant = $request->getAttribute('tenant');

        if (!$tenant instanceof Tenant) {
            return $next($request);
        }

        $route = $request->getAttribute('_route');
        if (empty($route['handler'])) {
            return $next($request);
        }

        [$class, $method] = $route['handler'];
        $container = Container::getInstance();
        $manager = $container->get(SubscriptionManagerInterface::class);

        $methodRef = new ReflectionMethod($class, $method);
        $classRef = new ReflectionClass($class);

        $feature = $this->firstAttribute($methodRef, $classRef, RequiresFeature::class);
        if ($feature !== null && !$manager->hasFeature($feature->feature)) {
            return $this->createErrorResponse($request, "Your plan does not include access to this feature.", 403);
        }

        $plan = $this->firstAttribute($methodRef, $classRef, RequiresPlan::class);
        if ($plan !== null && !$manager->onPlan($plan->plan)) {
            return $this->createErrorResponse($request, "This feature requires the '{$plan->plan}' plan.", 403);
        }

        $limit = $this->firstAttribute($methodRef, $classRef, WithinLimit::class);
        if ($limit !== null) {
            $qb = $container->get(QueryBuilderInterface::class);
            $count = $qb->setTable($limit->table)->count();
            if (!$manager->withinLimit($limit->resource, $count)) {
                $allowed = $manager->limitFor($limit->resource);
                return $this->createErrorResponse($request, "You have reached your plan limit of {$allowed} for '{$limit->resource}'.", 403);
            }
        }

        return $next($request);
    }

    private function firstAttribute(ReflectionMethod $method, ReflectionClass $class, string $attributeClass): ?object
    {
        $attrs = $method->getAttributes($attributeClass);
        if ($attrs) {
            return $attrs[0]->newInstance();
        }
        $attrs = $class->getAttributes($attributeClass);
        return $attrs ? $attrs[0]->newInstance() : null;
    }
}
