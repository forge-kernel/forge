<?php

declare(strict_types=1);

use Modules\ForgeDatabaseSQL\DB\Attributes\DropColumn;
use Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'users')]
#[DropColumn(table: 'users', name: 'temporary_field')]
class AlterUsersTableDropTemporaryField extends Migration
{
    // Declarative migration - removes the temporary_field column
}
