<?php


use Modules\ForgeMultiTenant\DTO\Tenant;
use Modules\ForgeMultiTenant\Exceptions\TenantNotFoundException;
use Modules\ForgeMultiTenant\Services\TenantManager;
use Forge\Core\DI\Container;

if (!function_exists('tenant')) {
    function tenant(): ?Tenant
    {
        $tenantMng = Container::getInstance()->get(TenantManager::class);
        return $tenantMng->current();
    }
}

if (!function_exists('requireTenant')) {
    function requireTenant(): Tenant
    {
        $tenant = tenant();
        if ($tenant === null) {
            throw new TenantNotFoundException('no-context');
        }
        return $tenant;
    }
}