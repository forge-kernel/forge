<?php
declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Middlewares;

use Modules\ForgeMultiTenant\Attributes\TenantScope;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeRouter\Http\{Middleware, Request, Response};
use ReflectionException;

final class ScopeMiddleware extends Middleware
{
    use ResponseHelper;

    /**
     * @throws ReflectionException
     */
    public function handle(Request $request, callable $next): Response
    {
        $route = $request->getAttribute('_route');
        $attr = $this->extractScope($route);
        $tenant = $request->getAttribute('tenant');

        $required = $attr?->value ?? 'both';

        if ($required === 'central' && $tenant !== null) {
            return $this->createErrorResponse($request, 'This page is not available', 403);
        }
        if ($required === 'tenant' && $tenant === null) {
            return $this->createErrorResponse($request, 'Page not found', 404);
        }

        return $next($request);
    }

    /**
     * @throws ReflectionException
     */
    private function extractScope(array $route): ?object
    {
        [$class, $method] = $route['handler'];
        $ref = new \ReflectionMethod($class, $method);
        $attrs = $ref->getAttributes(TenantScope::class);
        if ($attrs) return $attrs[0]->newInstance();

        $attrs = (new \ReflectionClass($class))->getAttributes(TenantScope::class);
        return $attrs ? $attrs[0]->newInstance() : null;
    }
}