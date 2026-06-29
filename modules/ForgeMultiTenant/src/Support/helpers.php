<?php


use Modules\ForgeMultiTenant\DTO\Tenant;
use Modules\ForgeMultiTenant\Services\TenantManager;
use Forge\Core\DI\Container;

if (!function_exists('tenant')) {
    function tenant(): ?Tenant
    {
        $tenantMng = Container::getInstance()->get(TenantManager::class);
        return $tenantMng->current();
    }
}