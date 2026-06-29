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
#[Table(name: 'payment_methods')]
#[Index(columns: ['tenant_id'], name: 'idx_payment_methods_tenant_id')]
#[Timestamps]
class CreatePaymentMethodsTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::STRING, primaryKey: true, length: 36)]
    public readonly string $id;

    #[Column(name: 'tenant_id', type: ColumnType::STRING, nullable: false, length: 36)]
    public readonly string $tenantId;

    #[Column(name: 'provider_name', type: ColumnType::STRING, nullable: false, length: 50)]
    public readonly string $providerName;

    #[Column(name: 'token', type: ColumnType::STRING, nullable: false, length: 255)]
    public readonly string $token;

    #[Column(name: 'type', type: ColumnType::STRING, nullable: false, length: 20)]
    public readonly string $type;

    #[Column(name: 'last_four', type: ColumnType::STRING, nullable: true, length: 4)]
    public readonly ?string $lastFour;

    #[Column(name: 'is_default', type: ColumnType::BOOLEAN, default: false)]
    public readonly bool $isDefault;
}
