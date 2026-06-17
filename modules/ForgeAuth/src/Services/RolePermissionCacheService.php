<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class RolePermissionCacheService
{
    private const string ROLE_CACHE_FILE =
        BASE_PATH . "/storage/framework/cache/role_cache.php";
    private const string PERMISSION_CACHE_FILE =
        BASE_PATH . "/storage/framework/cache/permissions_cache.php";

    public function warmCache(): void
    {
        $this->generateRoleCache();
        $this->generatePermissionCache();
    }

    public function flushCache(): void
    {
        if (file_exists(self::ROLE_CACHE_FILE)) {
            unlink(self::ROLE_CACHE_FILE);
        }

        if (file_exists(self::PERMISSION_CACHE_FILE)) {
            unlink(self::PERMISSION_CACHE_FILE);
        }
    }

    private function generateRoleCache(): void
    {
        $container = \Forge\Core\DI\Container::getInstance();
        $roleRepository = $container->get(
            \App\Modules\ForgeAuth\Repositories\RoleRepository::class,
        );
        $roles = $roleRepository->getAllRoles();

        $roleMap = [];
        foreach ($roles as $role) {
            $roleMap[$role->id] = [
                "id" => $role->id,
                "name" => $role->name,
                "description" => $role->description,
            ];
        }

        $cacheContent = "<?php return " . var_export($roleMap, true) . ";";
        $this->writeCacheFile(self::ROLE_CACHE_FILE, $cacheContent);
    }

    private function generatePermissionCache(): void
    {
        $container = \Forge\Core\DI\Container::getInstance();
        $permissionRepository = $container->get(
            \App\Modules\ForgeAuth\Repositories\PermissionRepository::class,
        );
        $permissions = $permissionRepository->getAll();

        $permissionMap = [];
        foreach ($permissions as $permission) {
            $permissionMap[$permission->id] = [
                "id" => $permission->id,
                "name" => $permission->name,
                "description" => $permission->description,
            ];
        }

        $cacheContent =
            "<?php return " . var_export($permissionMap, true) . ";";
        $this->writeCacheFile(self::PERMISSION_CACHE_FILE, $cacheContent);
    }

    private function writeCacheFile(string $path, string $content): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $content);
    }

    public function getRoleCache(): array
    {
        if (file_exists(self::ROLE_CACHE_FILE)) {
            return include self::ROLE_CACHE_FILE;
        }
        return [];
    }

    public function getPermissionCache(): array
    {
        if (file_exists(self::PERMISSION_CACHE_FILE)) {
            return include self::PERMISSION_CACHE_FILE;
        }
        return [];
    }
}
