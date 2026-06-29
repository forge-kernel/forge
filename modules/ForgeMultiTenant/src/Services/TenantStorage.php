<?php
declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Services;

final class TenantStorage
{
    public static function currentTenantStorageDirectory(string $path = ''): string
    {
        $tenant = tenant();
        if (!$tenant) {
            return BASE_PATH . '/storage/' . ltrim($path, '/');
        }
        $base = BASE_PATH . '/storage/tenants/' . $tenant->id;
        return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
    }

    public static function currentTenantPublicDirectory(string $path = ''): string
    {
        $tenant = tenant();
        if (!$tenant) {
            return BASE_PATH . '/public/' . ltrim($path, '/');
        }
        $base = BASE_PATH . '/public/tenants/' . $tenant->id;
        return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
    }
}
