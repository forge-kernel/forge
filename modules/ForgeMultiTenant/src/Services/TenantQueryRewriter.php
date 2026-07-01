<?php
declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Services;

use Modules\ForgeMultiTenant\DTO\Tenant;
use Modules\ForgeMultiTenant\Enums\Strategy;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Injectable;

#[Injectable]
final class TenantQueryRewriter
{
    private ?string $tenantId = null;
    private ?Strategy $strategy = null;

    public function setTenant(Tenant $tenant): void
    {
        $this->tenantId = $tenant->id;
        $this->strategy = $tenant->strategy;
    }

    public function scope(QueryBuilderInterface $qb): QueryBuilderInterface
    {
        if ($this->tenantId === null || $this->strategy !== Strategy::COLUMN) {
            return $qb;
        }
        return $qb->whereRaw('`tenant_id` = ?', [$this->tenantId]);
    }

    public function reset(): void
    {
        $this->tenantId = null;
        $this->strategy = null;
    }
}
