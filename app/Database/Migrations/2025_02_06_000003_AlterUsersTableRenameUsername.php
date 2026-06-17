<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\RenameColumn;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'users')]
#[RenameColumn(table: 'users', old: 'username', new: 'user_name')]
class AlterUsersTableRenameUsername extends Migration
{
    // Declarative migration - renames username to user_name
}
