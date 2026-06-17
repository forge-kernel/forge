<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'security')]
class CreatePermissionsTable extends Migration
{
    public function up(): void
    {
        $sql = $this->createTable('permissions', [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'name' => 'VARCHAR(255) UNIQUE NOT NULL',
            'description' => 'TEXT',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ]);
        $this->execute($sql);
    }

    public function down(): void
    {
        $this->execute($this->dropTable('permissions'));
    }
}