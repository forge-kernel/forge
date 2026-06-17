<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Migration;

use App\Modules\ForgeMultiTenant\Enums\Strategy;

final class TenantSchema
{
    public static function addTenantColumn(array &$columns): void
    {
        if (!class_exists(\App\Modules\ForgeMultiTenant\ForgeMultiTenantModule::class)) {
            return;
        }
        $tenant = tenant();
        if ($tenant !== null && Strategy::COLUMN === $tenant->strategy) {
            $columns['tenant_id'] = 'CHAR(36) NOT NULL';
        }
    }
}