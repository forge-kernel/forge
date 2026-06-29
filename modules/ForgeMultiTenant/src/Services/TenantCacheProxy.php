<?php
declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Services;

use Forge\Core\Cache\CacheManager;
use Modules\ForgeMultiTenant\DTO\Tenant;

final class TenantCacheProxy extends CacheManager
{
    private CacheManager $original;
    private Tenant $tenant;

    public function __construct(CacheManager $original, Tenant $tenant)
    {
        parent::__construct();
        $this->original = $original;
        $this->tenant = $tenant;
    }

    private function prefix(string $key): string
    {
        return "tenant_{$this->tenant->id}_{$key}";
    }

    public function get(string $key): mixed
    {
        return $this->original->get($this->prefix($key));
    }

    public function tags(array $tags): self
    {
        $this->original = $this->original->tags($tags);
        return $this;
    }

    public function clearTag(string $tag): void
    {
        $this->original->clearTag($tag);
    }

    public function delete(string $key): void
    {
        $this->original->delete($this->prefix($key));
    }

    public function getExpired(string $key): mixed
    {
        return $this->original->getExpired($this->prefix($key));
    }

    public function getRawEntry(string $key): mixed
    {
        return $this->original->getRawEntry($this->prefix($key));
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->original->set($this->prefix($key), $value, $ttl);
    }

    public function clear(): void
    {
        $this->original->clear();
    }
}
