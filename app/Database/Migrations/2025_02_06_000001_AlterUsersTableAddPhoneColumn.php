<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\AddColumn;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\DropColumn;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'users')]
#[AddColumn(table: 'users', name: 'phone', type: ColumnType::STRING, length: 20, nullable: true)]
#[Index(columns: ['phone'], name: 'idx_users_phone')]
class AlterUsersTableAddPhoneColumn extends Migration
{
    // Declarative migration - no code needed!
    // The attributes above define the ALTER TABLE operations
}
