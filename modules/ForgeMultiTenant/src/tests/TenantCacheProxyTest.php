<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Tests;

use Modules\ForgeMultiTenant\DTO\Tenant;
use Modules\ForgeMultiTenant\Enums\Strategy;
use Modules\ForgeMultiTenant\Services\TenantCacheProxy;
use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Forge\Core\Cache\CacheManager;
use Forge\Core\Config\Environment;

#[Group('multi-tenant')]
final class TenantCacheProxyTest extends TestCase
{
    private Tenant $tenant;
    private TenantCacheProxy $proxy;
    private CacheManager $original;

    #[BeforeEach]
    public function setup(): void
    {
        Environment::$instance = null;
        $env = Environment::getInstance();
        $env->hydrate(['CACHE_DRIVER' => 'memory']);

        $this->tenant = new Tenant('tenant-alpha', 'example.com', null, Strategy::COLUMN);
        $this->original = new CacheManager('memory');
        $this->proxy = new TenantCacheProxy($this->original, $this->tenant);
    }

    #[Test('set stores with tenant prefix')]
    public function set_prefixes_key(): void
    {
        $this->proxy->set('mykey', 'myvalue');
        $this->assertSame('myvalue', $this->original->get('tenant_tenant-alpha_mykey'));
    }

    #[Test('get retrieves with tenant prefix')]
    public function get_prefixes_key(): void
    {
        $this->original->set('tenant_tenant-alpha_mykey', 'stored');
        $this->assertSame('stored', $this->proxy->get('mykey'));
    }

    #[Test('get returns null for missing key')]
    public function get_missing_returns_null(): void
    {
        $this->assertNull($this->proxy->get('nonexistent'));
    }

    #[Test('set respects TTL')]
    public function set_with_ttl(): void
    {
        $this->proxy->set('mykey', 'value', 60);
        $raw = $this->original->getRawEntry('tenant_tenant-alpha_mykey');
        $this->assertNotNull($raw);
    }

    #[Test('delete removes with tenant prefix')]
    public function delete_prefixes_key(): void
    {
        $this->original->set('tenant_tenant-alpha_mykey', 'value');
        $this->original->set('other_key', 'other');

        $this->proxy->delete('mykey');

        $this->assertNull($this->original->get('tenant_tenant-alpha_mykey'));
        $this->assertSame('other', $this->original->get('other_key'));
    }

    #[Test('clear delegates to original')]
    public function clear_delegates(): void
    {
        $this->proxy->set('key1', 'val1');
        $this->proxy->set('key2', 'val2');

        $this->proxy->clear();

        $this->assertNull($this->proxy->get('key1'));
        $this->assertNull($this->proxy->get('key2'));
    }

    #[Test('tags returns proxy instance for chaining')]
    public function tags_returns_self(): void
    {
        $result = $this->proxy->tags(['scope:tenant']);
        $this->assertInstanceOf(TenantCacheProxy::class, $result);
    }
}
