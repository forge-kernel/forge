<?php
declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Services;

use Forge\Core\Session\SessionInterface;
use Modules\ForgeMultiTenant\DTO\Tenant;

final class TenantSessionProxy implements SessionInterface
{
    public function __construct(
        private readonly SessionInterface $original,
        private readonly Tenant $tenant
    ) {}

    private function prefix(string $key): string
    {
        return "tenant_{$this->tenant->id}_{$key}";
    }

    public function start(): void
    {
        $this->original->start();
    }

    public function save(): void
    {
        $this->original->save();
    }

    public function getId(): string
    {
        return $this->original->getId();
    }

    public function has(string $key): bool
    {
        return $this->original->has($this->prefix($key));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->original->get($this->prefix($key), $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->original->set($this->prefix($key), $value);
    }

    public function remove(string $key): void
    {
        $this->original->remove($this->prefix($key));
    }

    public function clear(): void
    {
        $prefix = "tenant_{$this->tenant->id}_";
        foreach ($this->original->all() as $k => $v) {
            if (str_starts_with($k, $prefix)) {
                $this->original->remove($k);
            }
        }
    }

    public function regenerate(bool $deleteOldSession = true): void
    {
        $this->original->regenerate($deleteOldSession);
    }

    public function isStarted(): bool
    {
        return $this->original->isStarted();
    }

    public function all(): array
    {
        $prefix = "tenant_{$this->tenant->id}_";
        $result = [];
        foreach ($this->original->all() as $k => $v) {
            if (str_starts_with($k, $prefix)) {
                $result[substr($k, strlen($prefix))] = $v;
            }
        }
        return $result;
    }
}
