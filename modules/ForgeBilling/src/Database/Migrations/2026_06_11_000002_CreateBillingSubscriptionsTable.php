<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Timestamps;
use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration('central')]
#[Table(name: 'billing_subscriptions')]
#[Index(columns: ['tenant_id'], name: 'idx_billing_subs_tenant_id')]
#[Index(columns: ['plan_id'], name: 'idx_billing_subs_plan_id')]
#[Timestamps]
class CreateBillingSubscriptionsTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::STRING, primaryKey: true, length: 36)]
    public readonly string $id;

    #[Column(name: 'tenant_id', type: ColumnType::STRING, nullable: false, length: 36)]
    public readonly string $tenantId;

    #[Column(name: 'plan_id', type: ColumnType::STRING, nullable: false, length: 36)]
    public readonly string $planId;

    #[Column(name: 'status', type: ColumnType::STRING, nullable: false, length: 20)]
    public readonly string $status;

    #[Column(name: 'trial_ends_at', type: ColumnType::DATETIME, nullable: true)]
    public readonly ?string $trialEndsAt;

    #[Column(name: 'current_period_ends_at', type: ColumnType::DATETIME, nullable: true)]
    public readonly ?string $currentPeriodEndsAt;

    #[Column(name: 'cancelled_at', type: ColumnType::DATETIME, nullable: true)]
    public readonly ?string $cancelledAt;
}
