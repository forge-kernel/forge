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
#[Table(name: 'invoice_items')]
#[Index(columns: ['invoice_id'], name: 'idx_invoice_items_invoice_id')]
#[Timestamps]
class CreateInvoiceItemsTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::STRING, primaryKey: true, length: 36)]
    public readonly string $id;

    #[Column(name: 'invoice_id', type: ColumnType::STRING, nullable: false, length: 36)]
    public readonly string $invoiceId;

    #[Column(name: 'description', type: ColumnType::STRING, nullable: false, length: 255)]
    public readonly string $description;

    #[Column(name: 'amount', type: ColumnType::DECIMAL, nullable: false, length: 10, precision: 2)]
    public readonly float $amount;

    #[Column(name: 'currency', type: ColumnType::STRING, nullable: false, length: 3, default: 'USD')]
    public readonly string $currency;

    #[Column(name: 'quantity', type: ColumnType::INTEGER, nullable: false, default: 1)]
    public readonly int $quantity;
}
