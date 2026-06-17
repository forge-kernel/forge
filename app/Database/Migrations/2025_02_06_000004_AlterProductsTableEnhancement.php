<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\AddColumn;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\DropColumn;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\RenameColumn;
use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

/**
 * Example of comprehensive ALTER TABLE operations using attributes
 * 
 * This migration demonstrates:
 * - Adding columns with various types
 * - Dropping deprecated columns
 * - Renaming columns for better naming
 * - Adding indexes on new columns
 */
#[GroupMigration(name: 'products')]
#[AddColumn(table: 'products', name: 'sku', type: ColumnType::STRING, length: 50, nullable: false)]
#[AddColumn(table: 'products', name: 'weight', type: ColumnType::DECIMAL, precision: 10, scale: 3, nullable: true)]
#[AddColumn(table: 'products', name: 'is_featured', type: ColumnType::BOOLEAN, default: false)]
#[RenameColumn(table: 'products', old: 'prod_name', new: 'name')]
#[DropColumn(table: 'products', name: 'legacy_field')]
#[Index(columns: ['sku'], name: 'idx_products_sku', unique: true)]
#[Index(columns: ['is_featured'], name: 'idx_products_featured')]
class AlterProductsTableEnhancement extends Migration
{
    // All operations defined declaratively via attributes
    // Clean, readable, and maintainable
}
