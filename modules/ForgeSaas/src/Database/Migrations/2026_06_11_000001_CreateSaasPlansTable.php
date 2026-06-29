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
#[Table(name: 'saas_plans')]
#[Index(columns: ['slug'], name: 'idx_saas_plans_slug')]
#[Timestamps]
class CreateSaasPlansTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::STRING, primaryKey: true, length: 36)]
    public readonly string $id;

    #[Column(name: 'name', type: ColumnType::STRING, nullable: false, length: 100)]
    public readonly string $name;

    #[Column(name: 'slug', type: ColumnType::STRING, nullable: false, unique: true, length: 100)]
    public readonly string $slug;

    #[Column(name: 'features', type: ColumnType::JSON, nullable: false)]
    public readonly string $features;

    #[Column(name: 'limits', type: ColumnType::JSON, nullable: false)]
    public readonly string $limits;

    #[Column(name: 'is_active', type: ColumnType::BOOLEAN, default: true)]
    public readonly bool $isActive;
}
