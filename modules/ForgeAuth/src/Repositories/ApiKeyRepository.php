<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Repositories;

use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;

class ApiKeyRepository
{
    private readonly QueryBuilderInterface $queryBuilder;

    public function __construct()
    {
        $this->queryBuilder = Container::getInstance()->get(QueryBuilderInterface::class);
    }

    public function findByKey(string $apiKey): ?array
    {
        return $this->queryBuilder
            ->table('api_keys')
            ->select('id', 'name', 'is_active', 'expires_at')
            ->where('api_key', '=', $apiKey)
            ->where('is_active', '=', 1)
            ->first();
    }

    public function findPermissionsByKeyId(int $keyId): array
    {
        $permissions = $this->queryBuilder
            ->table('api_key_permissions')
            ->join('permissions', 'api_key_permissions.permission_id', '=', 'permissions.id')
            ->where('api_key_permissions.api_key_id', '=', $keyId)
            ->get();

        return array_column($permissions, 'name');
    }

    public function hasPermission(int $keyId, string $permissionName): bool
    {
        return $this->queryBuilder
            ->table('api_key_permissions')
            ->join('permissions', 'api_key_permissions.permission_id', '=', 'permissions.id')
            ->where('api_key_permissions.api_key_id', '=', $keyId)
            ->where('permissions.name', '=', $permissionName)
            ->exists();
    }

    public function hasPermissions(int $keyId, array $permissionNames): array
    {
        if (empty($permissionNames)) {
            return [];
        }

        $permissions = $this->queryBuilder
            ->table('api_key_permissions')
            ->join('permissions', 'api_key_permissions.permission_id', '=', 'permissions.id')
            ->where('api_key_permissions.api_key_id', '=', $keyId)
            ->whereIn('permissions.name', $permissionNames)
            ->get();

        return array_column($permissions, 'name');
    }

    public function createApiKey(array $data): int
    {
        return $this->queryBuilder->table('api_keys')->insert($data);
    }

    public function createApiKeyPermissions(int $keyId, array $permissionIds): void
    {
        $data = array_map(fn($permissionId) => [
            'api_key_id' => $keyId,
            'permission_id' => $permissionId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $permissionIds);

        $this->queryBuilder->table('api_key_permissions')->insert($data);
    }

    public function deactivateApiKey(string $apiKey): int
    {
        return $this->queryBuilder
            ->table('api_keys')
            ->where('api_key', '=', $apiKey)
            ->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function cleanupExpiredKeys(): int
    {
        return $this->queryBuilder
            ->table('api_keys')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }
}