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


if (!function_exists('get_tenant_id')) {
    function get_tenant_id(): ?string
    {
        $tenantId = '';
        if (tenant() != null) {
            $tenantId = tenant()->id;
        }
        return $tenantId;
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

if (!function_exists('tenant_url')) {
    function tenant_url(?string $scheme = 'https'): ?string
    {
        $tenant = tenant();
        if ($tenant === null) {
            return null;
        }

        $host = $tenant->subdomain
            ? "{$tenant->subdomain}.{$tenant->domain}"
            : $tenant->domain;

        return "{$scheme}://{$host}";
    }
}
