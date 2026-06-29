<?php

declare(strict_types=1);

use Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use Modules\ForgeDatabaseSQL\DB\Attributes\Timestamps;
use Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration('tenant')]
#[Table(name: 'tenant_subscriptions')]
#[Index(columns: ['tenant_id'], name: 'idx_tenant_subscriptions_tenant')]
#[Index(columns: ['plan_id'], name: 'idx_tenant_subscriptions_plan')]
#[Timestamps]
class CreateTenantSubscriptionsTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::STRING, primaryKey: true, length: 36)]
    public readonly string $id;

    #[Column(name: 'tenant_id', type: ColumnType::STRING, nullable: false, length: 36)]
    public readonly string $tenantId;

    #[Column(name: 'plan_id', type: ColumnType::STRING, nullable: false, length: 36)]
    public readonly string $planId;

    #[Column(name: 'status', type: ColumnType::STRING, default: 'active', length: 20)]
    public readonly string $status;

    #[Column(name: 'trial_ends_at', type: ColumnType::DATETIME, nullable: true)]
    public readonly ?string $trialEndsAt;

    #[Column(name: 'current_period_ends_at', type: ColumnType::DATETIME, nullable: true)]
    public readonly ?string $currentPeriodEndsAt;
}
