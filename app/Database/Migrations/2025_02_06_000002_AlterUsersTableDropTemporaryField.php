<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\DropColumn;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'users')]
#[DropColumn(table: 'users', name: 'temporary_field')]
class AlterUsersTableDropTemporaryField extends Migration
{
    // Declarative migration - removes the temporary_field column
}
