<?php

declare(strict_types=1);

use Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use Modules\ForgeDatabaseSQL\DB\Attributes\Timestamps;
use Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration('central')]
#[Table(name: 'billing_plans')]
#[Index(columns: ['slug'], name: 'idx_billing_plans_slug')]
#[Timestamps]
class CreateBillingPlansTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::STRING, primaryKey: true, length: 36)]
    public readonly string $id;

    #[Column(name: 'name', type: ColumnType::STRING, nullable: false, length: 100)]
    public readonly string $name;

    #[Column(name: 'slug', type: ColumnType::STRING, nullable: false, unique: true, length: 100)]
    public readonly string $slug;

    #[Column(name: 'amount', type: ColumnType::DECIMAL, nullable: false, length: 10, precision: 2)]
    public readonly float $amount;

    #[Column(name: 'currency', type: ColumnType::STRING, nullable: false, length: 3, default: 'USD')]
    public readonly string $currency;

    #[Column(name: 'interval', type: ColumnType::STRING, nullable: false, length: 20)]
    public readonly string $interval;

    #[Column(name: 'features', type: ColumnType::JSON, nullable: false)]
    public readonly string $features;

    #[Column(name: 'is_active', type: ColumnType::BOOLEAN, default: true)]
    public readonly bool $isActive;
}
