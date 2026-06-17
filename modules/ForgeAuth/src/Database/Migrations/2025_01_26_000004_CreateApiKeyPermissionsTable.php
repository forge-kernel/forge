<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'security')]
class CreateApiKeyPermissionsTable extends Migration
{
    public function up(): void
    {
        $sql = $this->createTable('api_key_permissions', [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'api_key_id' => 'INTEGER NOT NULL',
            'permission_id' => 'INTEGER NOT NULL',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ]);
        $this->execute($sql);
        
        $fk1Sql = $this->addForeignKey('api_key_permissions', 'api_key_id', 'api_keys', 'id', 'CASCADE');
        if ($fk1Sql !== null) {
            $this->execute($fk1Sql);
        }
        
        $fk2Sql = $this->addForeignKey('api_key_permissions', 'permission_id', 'permissions', 'id', 'CASCADE');
        if ($fk2Sql !== null) {
            $this->execute($fk2Sql);
        }
        
        $this->execute('CREATE UNIQUE INDEX idx_api_key_permission_unique ON api_key_permissions (api_key_id, permission_id)');
    }

    public function down(): void
    {
        $this->execute($this->dropTable('api_key_permissions'));
    }
}