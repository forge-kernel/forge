<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Middlewares;

use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;
use Modules\ForgeMultiTenant\Services\CentralDomain;
use Modules\ForgeMultiTenant\Services\TenantConnectionFactory;
use Modules\ForgeMultiTenant\Services\TenantManager;
use Modules\ForgeRouter\Http\Middleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Traits\ResponseHelper;

class TenantAwareApiKeyMiddleware extends Middleware
{
    use ResponseHelper;

    public function __construct(
        private readonly TenantManager $tenantManager,
        private readonly TenantConnectionFactory $connectionFactory,
        private readonly ?QueryBuilderInterface $centralQueryBuilder = null,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->centralQueryBuilder === null) {
            return $next($request);
        }

        if (Container::getInstance()->has(DatabaseConnectionInterface::class)) {
            $apiKey = $request->getHeader('X-API-KEY', null);
            $requiredPermissions = $request->getAttribute('required_permissions', []);

            if (!$apiKey) {
                return $this->createResponse($request, 'Unauthorized: API key missing', 401);
            }

            $queryBuilder = $this->resolveCentralQueryBuilder();

            $keyRecord = $queryBuilder->reset()->setTable('api_keys')
                ->select('id')
                ->where('api_key', '=', $apiKey)
                ->first();

            if (!$keyRecord) {
                return $this->createResponse($request, 'Unauthorized: Invalid API key', 401);
            }

            $resolvedPermissions = [];
            foreach ($requiredPermissions as $permissionName) {
                $hasPermission = $queryBuilder->reset()->setTable('api_key_permissions')
                    ->join('permissions', 'api_key_permissions.permission_id', '=', 'permissions.id')
                    ->where('api_key_permissions.api_key_id', '=', $keyRecord['id'])
                    ->where('permissions.name', '=', $permissionName)
                    ->exists();

                if (!$hasPermission) {
                    return $this->createResponse($request, 'Unauthorized: Insufficient Permissions', 403);
                }
                $resolvedPermissions[] = $permissionName;
            }

            $request->setAttribute('api_key_permissions', $resolvedPermissions);
        }

        return $next($request);
    }

    private function resolveCentralQueryBuilder(): QueryBuilderInterface
    {
        $tenant = Container::getInstance()->get(TenantManager::class);

        $rawHost = $_SERVER['HTTP_HOST'] ?? '';
        $host = CentralDomain::stripPort($rawHost);
        $resolvedTenant = $tenant->resolveByDomain($host) ?? null;

        if ($resolvedTenant !== null) {
            $connection = $this->connectionFactory->forTenant($resolvedTenant);
            $isTenantDb = $resolvedTenant->strategy->value === 'database';

            if ($isTenantDb) {
                return clone $this->centralQueryBuilder;
            }
        }

        return clone $this->centralQueryBuilder;
    }
}
