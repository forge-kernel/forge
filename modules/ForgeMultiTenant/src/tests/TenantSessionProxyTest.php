<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Tests;

use Modules\ForgeMultiTenant\DTO\Tenant;
use Modules\ForgeMultiTenant\Enums\Strategy;
use Modules\ForgeMultiTenant\Services\TenantSessionProxy;
use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Forge\Core\Session\SessionInterface;

#[Group('multi-tenant')]
final class TenantSessionProxyTest extends TestCase
{
    private InMemorySession $original;
    private TenantSessionProxy $proxy;
    private Tenant $tenant;

    #[BeforeEach]
    public function setup(): void
    {
        $this->tenant = new Tenant('tenant-alpha', 'example.com', null, Strategy::COLUMN);
        $this->original = new InMemorySession();
        $this->proxy = new TenantSessionProxy($this->original, $this->tenant);
    }

    #[Test('get prefixes key with tenant ID')]
    public function get_prefixes_key(): void
    {
        $this->original->set('tenant_tenant-alpha_mykey', 'stored-value');
        $this->assertSame('stored-value', $this->proxy->get('mykey'));
    }

    #[Test('get returns default for missing key')]
    public function get_default_for_missing(): void
    {
        $this->assertNull($this->proxy->get('nonexistent'));
        $this->assertSame('fallback', $this->proxy->get('nonexistent', 'fallback'));
    }

    #[Test('set stores with tenant prefix')]
    public function set_prefixes_key(): void
    {
        $this->proxy->set('mykey', 'myvalue');
        $this->assertSame('myvalue', $this->original->get('tenant_tenant-alpha_mykey'));
    }

    #[Test('has checks with tenant prefix')]
    public function has_prefixes_key(): void
    {
        $this->assertFalse($this->proxy->has('mykey'));

        $this->original->set('tenant_tenant-alpha_mykey', 'value');
        $this->assertTrue($this->proxy->has('mykey'));
    }

    #[Test('remove deletes with tenant prefix')]
    public function remove_prefixes_key(): void
    {
        $this->original->set('tenant_tenant-alpha_mykey', 'value');
        $this->proxy->remove('mykey');
        $this->assertFalse($this->original->has('tenant_tenant-alpha_mykey'));
    }

    #[Test('all returns only tenant-scoped keys without prefix')]
    public function all_returns_scoped_keys(): void
    {
        $this->original->set('tenant_tenant-alpha_key1', 'val1');
        $this->original->set('tenant_tenant-alpha_key2', 'val2');
        $this->original->set('global_key', 'global');

        $result = $this->proxy->all();

        $this->assertArrayHasKey('key1', $result);
        $this->assertArrayHasKey('key2', $result);
        $this->assertArrayNotHasKey('global_key', $result);
        $this->assertCount(2, $result);
    }

    #[Test('clear removes only tenant-scoped keys')]
    public function clear_removes_scoped_only(): void
    {
        $this->original->set('tenant_tenant-alpha_key1', 'val1');
        $this->original->set('tenant_tenant-alpha_key2', 'val2');
        $this->original->set('global_key', 'global');

        $this->proxy->clear();

        $this->assertFalse($this->original->has('tenant_tenant-alpha_key1'));
        $this->assertFalse($this->original->has('tenant_tenant-alpha_key2'));
        $this->assertTrue($this->original->has('global_key'));
    }

    #[Test('start delegates to original')]
    public function start_delegates(): void
    {
        $this->proxy->start();
        $this->assertTrue($this->original->started);
    }

    #[Test('save delegates to original')]
    public function save_delegates(): void
    {
        $this->original->set('some_key', 'value');
        $this->proxy->save();
        $this->assertTrue($this->original->saved);
    }

    #[Test('getId delegates to original')]
    public function get_id_delegates(): void
    {
        $this->original->sessionId = 'test-session-id';
        $this->assertSame('test-session-id', $this->proxy->getId());
    }

    #[Test('regenerate delegates to original')]
    public function regenerate_delegates(): void
    {
        $this->proxy->regenerate(false);
        $this->assertTrue($this->original->regenerated);
        $this->assertFalse($this->original->deleteOld);
    }

    #[Test('isStarted delegates to original')]
    public function is_started_delegates(): void
    {
        $this->assertFalse($this->proxy->isStarted());
        $this->proxy->start();
        $this->assertTrue($this->proxy->isStarted());
    }
}

final class InMemorySession implements SessionInterface
{
    public array $data = [];
    public bool $started = false;
    public bool $saved = false;
    public string $sessionId = 'session-id';
    public bool $regenerated = false;
    public ?bool $deleteOld = null;

    public function start(): void { $this->started = true; }
    public function save(): void { $this->saved = true; }
    public function getId(): string { return $this->sessionId; }
    public function has(string $key): bool { return array_key_exists($key, $this->data); }
    public function get(string $key, mixed $default = null): mixed { return $this->data[$key] ?? $default; }
    public function set(string $key, mixed $value): void { $this->data[$key] = $value; }
    public function remove(string $key): void { unset($this->data[$key]); }
    public function clear(): void { $this->data = []; }
    public function regenerate(bool $deleteOldSession = true): void { $this->regenerated = true; $this->deleteOld = $deleteOldSession; }
    public function isStarted(): bool { return $this->started; }
    public function all(): array { return $this->data; }
}
