<?php

declare(strict_types=1);

use Modules\ForgeDatabaseSQL\DB\Attributes\AddColumn;
use Modules\ForgeDatabaseSQL\DB\Attributes\DropColumn;
use Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'users')]
#[AddColumn(table: 'users', name: 'phone', type: ColumnType::STRING, length: 20, nullable: true)]
#[Index(columns: ['phone'], name: 'idx_users_phone')]
class AlterUsersTableAddPhoneColumn extends Migration
{
    // Declarative migration - no code needed!
    // The attributes above define the ALTER TABLE operations
}
