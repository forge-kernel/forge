<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Services;

use Modules\ForgeAuth\Contracts\AuthUserInterface;
use Modules\ForgeAuth\Repositories\PermissionRepository;
use Modules\ForgeAuth\Repositories\ApiKeyRepository;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class ApiKeyService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly ApiKeyRepository $apiKeyRepository,
    ) {}

    public function validateApiKey(string $apiKey, ?AuthUserInterface $user = null): bool
    {
        $keyRecord = $this->apiKeyRepository->findByKey($apiKey);

        if (!$keyRecord) {
            return false;
        }

        if (!$user) {
            return true;
        }

        return $this->validateKeyPermissions($keyRecord["id"], $user);
    }

    public function validateKeyPermissions(int $keyId, AuthUserInterface $user): bool
    {
        if (!$user) {
            return false;
        }

        $permissions = $this->apiKeyRepository->findPermissionsByKeyId($keyId);

        if (empty($permissions)) {
            return false;
        }

        $this->checkUserPermissions($permissions, $user);
        return true;
    }

    public function getApiKeyInfo(string $apiKey): ?array
    {
        $keyRecord = $this->apiKeyRepository->findByKey($apiKey);

        if (!$keyRecord) {
            return null;
        }

        $permissions = $this->apiKeyRepository->findPermissionsByKeyId($keyRecord["id"]);

        return [
            "id" => $keyRecord["id"],
            "name" => $keyRecord["name"],
            "is_active" => $keyRecord["is_active"],
            "expires_at" => $keyRecord["expires_at"],
            "permissions" => $permissions,
        ];
    }

    public function createApiKey(
        string $name,
        array $permissionNames,
        ?string $expiresAt = null,
    ): string {
        $apiKey = $this->generateApiKey();
        $data = [
            "api_key" => $apiKey,
            "name" => $name,
            "is_active" => 1,
            "expires_at" => $expiresAt,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
        ];

        $keyId = $this->apiKeyRepository->createApiKey($data);

        $permissionIds = [];
        foreach ($permissionNames as $permissionName) {
            $permission = $this->permissionRepository->findByName(
                $permissionName,
            );
            if ($permission) {
                $permissionIds[] = $permission->id;
            }
        }

        if (!empty($permissionIds)) {
            $this->apiKeyRepository->createApiKeyPermissions($keyId, $permissionIds);
        }

        return $apiKey;
    }

    public function revokeApiKey(string $apiKey): bool
    {
        return $this->apiKeyRepository->deactivateApiKey($apiKey) > 0;
    }

    public function rotateApiKey(
        string $oldApiKey,
        string $name,
        array $permissionNames,
    ): string {
        $newApiKey = $this->generateApiKey();

        $this->apiKeyRepository->deactivateApiKey($oldApiKey);

        $data = [
            "api_key" => $newApiKey,
            "name" => $name,
            "is_active" => 1,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
        ];

        $keyId = $this->apiKeyRepository->createApiKey($data);

        $permissionIds = [];
        foreach ($permissionNames as $permissionName) {
            $permission = $this->permissionRepository->findByName(
                $permissionName,
            );
            if ($permission) {
                $permissionIds[] = $permission->id;
            }
        }

        if (!empty($permissionIds)) {
            $this->apiKeyRepository->createApiKeyPermissions($keyId, $permissionIds);
        }

        return $newApiKey;
    }

    public function cleanupExpiredKeys(): int
    {
        return $this->apiKeyRepository->cleanupExpiredKeys();
    }

    private function checkUserPermissions(array $permissions, AuthUserInterface $user): void
    {
        foreach ($permissions as $permissionName) {
            if (!$this->permissionRepository->findByName($permissionName)) {
                throw new \InvalidArgumentException(
                    "Permission '{$permissionName}' not found in system",
                );
            }
        }
    }

    private function generateApiKey(): string
    {
        return bin2hex(random_bytes(32));
    }
}
