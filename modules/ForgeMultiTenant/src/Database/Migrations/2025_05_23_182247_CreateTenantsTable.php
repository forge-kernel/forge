<?php

declare(strict_types=1);

use Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use Modules\ForgeDatabaseSQL\DB\Attributes\MetaData;
use Modules\ForgeDatabaseSQL\DB\Attributes\SoftDelete;
use Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use Modules\ForgeDatabaseSQL\DB\Attributes\Timestamps;
use Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration('tenant')]
#[Table(name: 'tenants')]
#[Index(columns: ['domain'], name: 'idx_tenants_domain')]
#[Index(columns: ['subdomain'], name: 'idx_tenants_subdomain')]
#[MetaData]
#[Timestamps]
#[SoftDelete]
class CreateTenantsTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::STRING, primaryKey: true, length: 36)]
    public readonly string $id;

    #[Column(name: 'domain', type: ColumnType::STRING, nullable: false, length: 255)]
    public readonly string $domain;

    #[Column(name: 'subdomain', type: ColumnType::STRING, nullable: true, length: 255)]
    public readonly ?string $subdomain;

    #[Column(name: 'strategy', type: ColumnType::STRING, default: 'column', length: 20)]
    public readonly string $strategy;

    #[Column(name: 'db_name', type: ColumnType::STRING, nullable: true, length: 64)]
    public readonly ?string $dbName;

    #[Column(name: 'connection', type: ColumnType::STRING, nullable: true, length: 64)]
    public readonly ?string $connection;
}
