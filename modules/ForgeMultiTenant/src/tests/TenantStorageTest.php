<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Tests;

use Modules\ForgeMultiTenant\DTO\Tenant;
use Modules\ForgeMultiTenant\Enums\Strategy;
use Modules\ForgeMultiTenant\Services\TenantManager;
use Modules\ForgeMultiTenant\Services\TenantStorage;
use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Forge\Core\DI\Container;

#[Group('multi-tenant')]
final class TenantStorageTest extends TestCase
{
    private TenantManager $tenantManager;

    #[BeforeEach]
    public function setup(): void
    {
        $this->tenantManager = new TenantManager(dataCallback: fn() => []);

        Container::getInstance()->setInstance(TenantManager::class, $this->tenantManager);
    }

    #[Test('currentTenantStorageDirectory returns base storage path when no tenant')]
    public function storage_dir_no_tenant(): void
    {
        $path = TenantStorage::currentTenantStorageDirectory();
        $this->assertStringContainsString('/storage', $path);
        $this->assertStringNotContainsString('/tenants/', $path);
    }

    #[Test('currentTenantStorageDirectory appends subpath')]
    public function storage_dir_with_subpath(): void
    {
        $path = TenantStorage::currentTenantStorageDirectory('logs/app.log');
        $this->assertStringContainsString('/storage/logs/app.log', $path);
    }

    #[Test('currentTenantPublicDirectory returns base public path when no tenant')]
    public function public_dir_no_tenant(): void
    {
        $path = TenantStorage::currentTenantPublicDirectory();
        $this->assertStringContainsString('/public', $path);
        $this->assertStringNotContainsString('/tenants/', $path);
    }

    #[Test('currentTenantPublicDirectory appends subpath')]
    public function public_dir_with_subpath(): void
    {
        $path = TenantStorage::currentTenantPublicDirectory('assets/logo.png');
        $this->assertStringContainsString('/public/assets/logo.png', $path);
    }

    #[Test('storage directory includes tenant ID when active')]
    public function storage_dir_with_tenant(): void
    {
        $this->setCurrentTenant('tenant-alpha');
        $path = TenantStorage::currentTenantStorageDirectory();
        $this->assertStringContainsString('/tenants/tenant-alpha', $path);
    }

    #[Test('public directory includes tenant ID when active')]
    public function public_dir_with_tenant(): void
    {
        $this->setCurrentTenant('tenant-alpha');
        $path = TenantStorage::currentTenantPublicDirectory();
        $this->assertStringContainsString('/public/tenants/tenant-alpha', $path);
    }

    #[Test('storage directory with subpath includes tenant ID')]
    public function storage_dir_with_tenant_and_subpath(): void
    {
        $this->setCurrentTenant('tenant-beta');
        $path = TenantStorage::currentTenantStorageDirectory('uploads');
        $this->assertStringContainsString('/tenants/tenant-beta/uploads', $path);
    }

    private function setCurrentTenant(string $id): void
    {
        $ref = new \ReflectionProperty(TenantManager::class, 'current');
        $ref->setAccessible(true);
        $tenant = new Tenant($id, 'test.com', null, Strategy::COLUMN);
        $ref->setValue($this->tenantManager, $tenant);

        $container = Container::getInstance();
        $container->setInstance(TenantManager::class, $this->tenantManager);
    }
}
