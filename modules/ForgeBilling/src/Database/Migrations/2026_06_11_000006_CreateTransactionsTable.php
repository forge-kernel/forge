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
#[Table(name: 'transactions')]
#[Index(columns: ['invoice_id'], name: 'idx_transactions_invoice_id')]
#[Index(columns: ['tenant_id'], name: 'idx_transactions_tenant_id')]
#[Timestamps]
class CreateTransactionsTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::STRING, primaryKey: true, length: 36)]
    public readonly string $id;

    #[Column(name: 'invoice_id', type: ColumnType::STRING, nullable: false, length: 36)]
    public readonly string $invoiceId;

    #[Column(name: 'tenant_id', type: ColumnType::STRING, nullable: false, length: 36)]
    public readonly string $tenantId;

    #[Column(name: 'provider_transaction_id', type: ColumnType::STRING, nullable: false, length: 255)]
    public readonly string $providerTransactionId;

    #[Column(name: 'amount', type: ColumnType::DECIMAL, nullable: false, length: 10, precision: 2)]
    public readonly float $amount;

    #[Column(name: 'currency', type: ColumnType::STRING, nullable: false, length: 3, default: 'USD')]
    public readonly string $currency;

    #[Column(name: 'status', type: ColumnType::STRING, nullable: false, length: 20)]
    public readonly string $status;

    #[Column(name: 'provider_response', type: ColumnType::JSON, nullable: true)]
    public readonly ?string $providerResponse;
}
