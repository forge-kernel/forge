<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Tests;

use Modules\ForgeMultiTenant\DTO\Tenant;
use Modules\ForgeMultiTenant\Exceptions\TenantNotFoundException;
use Modules\ForgeMultiTenant\Services\TenantManager;
use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;

#[Group('multi-tenant')]
final class TenantManagerTest extends TestCase
{
    private array $sampleTenants;

    #[BeforeEach]
    public function setup(): void
    {
        $this->sampleTenants = [
            [
                'id' => 'tenant-alpha',
                'domain' => 'example.com',
                'subdomain' => null,
                'strategy' => 'column',
                'db_name' => null,
                'connection' => null,
            ],
            [
                'id' => 'tenant-beta',
                'domain' => 'forge-v3.test',
                'subdomain' => 'customer1',
                'strategy' => 'database',
                'db_name' => 'forge_tenant_beta',
                'connection' => null,
            ],
            [
                'id' => 'tenant-gamma',
                'domain' => 'forge-v3.test',
                'subdomain' => 'customer2',
                'strategy' => 'column',
                'db_name' => null,
                'connection' => null,
            ],
        ];
    }

    private function createManager(?array $rows = null): TenantManager
    {
        $data = $rows ?? $this->sampleTenants;
        return new TenantManager(dataCallback: fn() => $data);
    }

    // --- resolveByDomain ---

    #[Test('resolveByDomain returns null for localhost')]
    public function resolve_by_domain_localhost(): void
    {
        $manager = $this->createManager();
        $this->assertNull($manager->resolveByDomain('localhost'));
        $this->assertNull($manager->resolveByDomain('127.0.0.1'));
        $this->assertNull($manager->resolveByDomain('[::1]'));
    }

    #[Test('resolveByDomain returns null for central domain')]
    public function resolve_by_domain_central(): void
    {
        $manager = $this->createManager();
        $this->assertNull($manager->resolveByDomain('forge-v3.test'));
    }

    #[Test('resolveByDomain matches domain tenant (no subdomain)')]
    public function resolve_by_domain_domain_tenant(): void
    {
        $manager = $this->createManager();
        $tenant = $manager->resolveByDomain('example.com');
        $this->assertNotNull($tenant);
        $this->assertSame('tenant-alpha', $tenant->id);
        $this->assertSame('example.com', $tenant->domain);
        $this->assertNull($tenant->subdomain);
    }

    #[Test('resolveByDomain matches subdomain tenant')]
    public function resolve_by_domain_subdomain_tenant(): void
    {
        $manager = $this->createManager();
        $tenant = $manager->resolveByDomain('customer1.forge-v3.test');
        $this->assertNotNull($tenant);
        $this->assertSame('tenant-beta', $tenant->id);
        $this->assertSame('forge-v3.test', $tenant->domain);
        $this->assertSame('customer1', $tenant->subdomain);
    }

    #[Test('resolveByDomain returns null for unknown host')]
    public function resolve_by_domain_unknown(): void
    {
        $manager = $this->createManager();
        $this->assertNull($manager->resolveByDomain('unknown.example.com'));
    }

    #[Test('resolveByDomain sets current tenant')]
    public function resolve_by_domain_sets_current(): void
    {
        $manager = $this->createManager();
        $manager->resolveByDomain('example.com');
        $this->assertNotNull($manager->current());
        $this->assertSame('tenant-alpha', $manager->tenantId());
    }

    #[Test('resolveByDomain clears current on no match')]
    public function resolve_by_domain_no_match_clears_current(): void
    {
        $manager = $this->createManager();
        $manager->resolveByDomain('example.com');
        $this->assertNotNull($manager->current());

        $manager->resolveByDomain('unknown.dev');
        $this->assertNull($manager->current());
    }

    // --- find ---

    #[Test('find returns tenant by id')]
    public function find_by_id(): void
    {
        $manager = $this->createManager();
        $tenant = $manager->find('tenant-alpha');
        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertSame('tenant-alpha', $tenant->id);
    }

    #[Test('find throws TenantNotFoundException for missing id')]
    public function find_missing_throws(): void
    {
        $manager = $this->createManager();
        $this->shouldFail(
            fn() => $manager->find('non-existent'),
            TenantNotFoundException::class,
        );
    }

    // --- all ---

    #[Test('all returns all tenants')]
    public function all_tenants(): void
    {
        $manager = $this->createManager();
        $tenants = $manager->all();
        $this->assertCount(3, $tenants);
        $this->assertContainsOnlyInstancesOf(Tenant::class, $tenants);
    }

    #[Test('all returns empty array when no tenants')]
    public function all_empty(): void
    {
        $manager = $this->createManager([]);
        $tenants = $manager->all();
        $this->assertSame([], $tenants);
    }

    // --- current / tenantId ---

    #[Test('current returns null before any resolution')]
    public function current_null_initially(): void
    {
        $manager = $this->createManager();
        $this->assertNull($manager->current());
        $this->assertNull($manager->tenantId());
    }

    // --- request-scoped caching ---

    #[Test('loadTenants caches results within same request')]
    public function caches_results(): void
    {
        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;
            return $this->sampleTenants;
        };

        $manager = new TenantManager(dataCallback: $callback);

        $manager->resolveByDomain('example.com');
        $this->assertSame(1, $callCount, 'Should fetch on first call');

        $manager->resolveByDomain('customer1.forge-v3.test');
        $this->assertSame(1, $callCount, 'Should NOT fetch again — uses host-map cache');

        $manager->find('tenant-alpha');
        $this->assertSame(1, $callCount, 'Should NOT fetch again — uses id-map cache');

        $manager->all();
        $this->assertSame(1, $callCount, 'Should NOT fetch again — uses id-map cache');
    }

    #[Test('clearCache resets cached data')]
    public function clear_cache_resets(): void
    {
        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;
            return $this->sampleTenants;
        };

        $manager = new TenantManager(dataCallback: $callback);
        $manager->all();
        $this->assertSame(1, $callCount, 'First call fetches data');

        $manager->all();
        $this->assertSame(1, $callCount, 'Cached, no re-fetch');

        $manager->clearCache();

        $manager->all();
        $this->assertSame(2, $callCount, 'Re-fetches after cache clear');
    }

    // --- arrayToDto edge cases ---

    #[Test('arrayToDto handles missing strategy with default')]
    public function handles_missing_strategy(): void
    {
        $rows = [
            [
                'id' => 'tenant-no-strategy',
                'domain' => 'test.com',
                'subdomain' => null,
            ],
        ];
        $manager = $this->createManager($rows);
        $tenant = $manager->find('tenant-no-strategy');
        $this->assertNotNull($tenant);
        $this->assertSame('column', $tenant->strategy->value);
    }

    #[Test('arrayToDto handles unknown strategy gracefully')]
    public function handles_unknown_strategy(): void
    {
        $rows = [
            [
                'id' => 'tenant-bad-strategy',
                'domain' => 'test.com',
                'subdomain' => null,
                'strategy' => 'invalid_strategy_value',
            ],
        ];
        $manager = $this->createManager($rows);
        $tenant = $manager->find('tenant-bad-strategy');
        $this->assertNotNull($tenant);
        $this->assertSame('column', $tenant->strategy->value, 'Falls back to column for unknown strategy');
    }

    // --- empty data store ---

    #[Test('resolveByDomain on empty data returns null')]
    public function resolve_empty_data(): void
    {
        $manager = $this->createManager([]);
        $this->assertNull($manager->resolveByDomain('anything.com'));
    }

    #[Test('find on empty data throws')]
    public function find_empty_data(): void
    {
        $manager = $this->createManager([]);
        $this->shouldFail(
            fn() => $manager->find('anything'),
            TenantNotFoundException::class,
        );
    }

    private function assertContainsOnlyInstancesOf(string $class, array $items): void
    {
        foreach ($items as $item) {
            $this->assertInstanceOf($class, $item);
        }
    }
}
