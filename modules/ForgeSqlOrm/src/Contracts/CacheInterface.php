<?php
declare(strict_types=1);

namespace Modules\ForgeSqlOrm\Contracts;

interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, ?int $ttl = null): void;
    public function forget(string $key): void;
    public function invalidate(string $prefix): void;
    public function clear(): void;
    public function generateKey(string $table, string $method, mixed ...$args): string;
}
