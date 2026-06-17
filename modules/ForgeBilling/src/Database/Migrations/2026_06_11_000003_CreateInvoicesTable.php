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
#[Table(name: 'invoices')]
#[Index(columns: ['tenant_id'], name: 'idx_invoices_tenant_id')]
#[Index(columns: ['subscription_id'], name: 'idx_invoices_subscription_id')]
#[Index(columns: ['number'], name: 'idx_invoices_number', unique: true)]
#[Timestamps]
class CreateInvoicesTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::STRING, primaryKey: true, length: 36)]
    public readonly string $id;

    #[Column(name: 'tenant_id', type: ColumnType::STRING, nullable: false, length: 36)]
    public readonly string $tenantId;

    #[Column(name: 'subscription_id', type: ColumnType::STRING, nullable: false, length: 36)]
    public readonly string $subscriptionId;

    #[Column(name: 'number', type: ColumnType::STRING, nullable: false, length: 50)]
    public readonly string $number;

    #[Column(name: 'amount', type: ColumnType::DECIMAL, nullable: false, length: 10, precision: 2)]
    public readonly float $amount;

    #[Column(name: 'currency', type: ColumnType::STRING, nullable: false, length: 3, default: 'USD')]
    public readonly string $currency;

    #[Column(name: 'status', type: ColumnType::STRING, nullable: false, length: 20)]
    public readonly string $status;

    #[Column(name: 'paid_at', type: ColumnType::DATETIME, nullable: true)]
    public readonly ?string $paidAt;

    #[Column(name: 'due_date', type: ColumnType::DATETIME, nullable: true)]
    public readonly ?string $dueDate;
}
