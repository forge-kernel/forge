<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Services;

use App\Modules\ForgeMultiTenant\DTO\Tenant;
use App\Modules\ForgeMultiTenant\Enums\Strategy;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class TenantQueryRewriter
{
    private static ?string $tenantId = null;
    private static ?Strategy $strategy = null;

    public static function setTenant(Tenant $tenant): void
    {
        self::$tenantId = $tenant->id;
        self::$strategy = $tenant->strategy;
    }

    public static function scope(QueryBuilderInterface $qb): QueryBuilderInterface
    {
        if (self::$tenantId === null || self::$strategy !== Strategy::COLUMN) {
            return $qb;
        }
        return $qb->whereRaw('`tenant_id` = ?', [self::$tenantId]);
    }
}