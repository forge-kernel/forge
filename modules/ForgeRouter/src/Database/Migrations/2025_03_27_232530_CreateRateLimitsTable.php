<?php

declare(strict_types=1);

use Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'security')]
class CreateRateLimitsTable extends Migration
{
  public function up(): void
  {
    $sql = $this->createTable('rate_limits', [
      'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
      'ip_address' => 'VARCHAR(255) NOT NULL',
      'request_count' => 'INTEGER NOT NULL DEFAULT 1',
      'last_request' => 'TIMESTAMP NULL',
    ]);
    $this->execute($sql);

    $indexSql = $this->createIndex('rate_limits', 'idx_rate_limits_id', ['id']);
    $indexSql2 = $this->createIndex('rate_limits', 'idx_rate_limits_ip_address', ['ip_address']);
    $this->execute($indexSql);
    $this->execute($indexSql2);
  }

  public function down(): void
  {
    $this->execute($this->dropTable('rate_limits'));
  }
}
