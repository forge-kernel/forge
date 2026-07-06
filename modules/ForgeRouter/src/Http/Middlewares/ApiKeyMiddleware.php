<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\Contracts\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;
use Modules\ForgeRouter\Http\Middleware as MiddlewareImpl;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\Middleware;
use Modules\ForgeRouter\Traits\ResponseHelper;

#[Middleware(group: 'api', order: 1, allowDuplicate: true, enabled: true)]
class ApiKeyMiddleware extends MiddlewareImpl
{
    use ResponseHelper;

    public function __construct(private readonly ?QueryBuilderInterface $queryBuilder = null)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->queryBuilder === null) {
            return $next($request);
        }

        if (Container::getInstance()->has(DatabaseConnectionInterface::class)) {
            $apiKey = $request->getHeader('X-API-KEY', null);
            $requiredPermissions = $request->getAttribute('required_permissions', []);

            if (!$apiKey) {
                return $this->createResponse($request, 'Unauthorized: API key missing', 401);
            }

            $queryBuilder = $this->queryBuilder->reset()->setTable('api_keys');
            $keyRecord = $queryBuilder->setTable('api_keys')
                ->select('id')
                ->where('api_key', '=', $apiKey)
                ->first();

            if (!$keyRecord) {
                return $this->createResponse($request, 'Unauthorized: Invalid API key', 401);
            }

            $resolvedPermissions = [];
            foreach ($requiredPermissions as $permissionName) {
                $queryBuilder = $this->queryBuilder->reset()->setTable('api_key_permissions');
                $hasPermission = $queryBuilder->setTable('api_key_permissions')
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
}
