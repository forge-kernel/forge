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
