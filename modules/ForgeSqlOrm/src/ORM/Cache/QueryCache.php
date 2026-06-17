<?php

declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM\Cache;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class QueryCache
{
    private array $cache = [];
    private array $ttl = [];
    private int $defaultTtl;

    public function __construct(int $defaultTtl = 3600)
    {
        $this->defaultTtl = $defaultTtl;
    }

    public function get(string $key): mixed
    {
        if (!isset($this->cache[$key])) {
            return null;
        }

        if (isset($this->ttl[$key]) && $this->ttl[$key] < time()) {
            unset($this->cache[$key], $this->ttl[$key]);
            return null;
        }

        return $this->cache[$key];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->cache[$key] = $value;
        $this->ttl[$key] = time() + ($ttl ?? $this->defaultTtl);
    }

    public function forget(string $key): void
    {
        unset($this->cache[$key], $this->ttl[$key]);
    }

    public function invalidate(string $prefix): void
    {
        foreach (array_keys($this->cache) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->cache[$key], $this->ttl[$key]);
            }
        }
    }

    public function clear(): void
    {
        $this->cache = [];
        $this->ttl = [];
    }

    public function generateKey(string $table, string $method, mixed ...$args): string
    {
        $argsHash = md5(serialize($args));
        return "{$table}:{$method}:{$argsHash}";
    }
}

