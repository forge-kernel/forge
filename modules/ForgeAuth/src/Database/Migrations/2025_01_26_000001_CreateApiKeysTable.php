<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'security')]
class CreateApiKeysTable extends Migration
{
    public function up(): void
    {
        $sql = $this->createTable('api_keys', [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'api_key' => 'VARCHAR(255) UNIQUE NOT NULL',
            'name' => 'VARCHAR(255) NULL',
            'is_active' => 'BOOLEAN DEFAULT 1',
            'expires_at' => 'DATETIME NULL',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ]);
        $this->execute($sql);
    }

    public function down(): void
    {
        $this->execute($this->dropTable('api_keys'));
    }
}