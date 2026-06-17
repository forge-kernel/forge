<?php

declare(strict_types=1);

use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'security')]
class CreateCircuitBreakerTable extends Migration
{
    public function up(): void
    {
        $sql = $this->createTable('circuit_breaker', [
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'ip_address' => 'VARCHAR(45) NOT NULL',
            'fail_count' => 'INT NOT NULL DEFAULT 1',
            'first_failure' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        ]);
        $this->execute($sql);
    }

    public function down(): void
    {
        $this->execute($this->dropTable('circuit_breaker'));
    }
}
