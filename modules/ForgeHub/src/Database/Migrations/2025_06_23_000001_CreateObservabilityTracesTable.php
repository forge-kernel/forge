<?php

declare(strict_types=1);

use Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'forgehub')]
class CreateObservabilityTracesTable extends Migration
{
    public function up(): void
    {
        $sql = $this->createTable('observability_traces', [
            'id' => 'CHAR(32) PRIMARY KEY',
            'name' => 'VARCHAR(255) NOT NULL',
            'started_at' => 'DECIMAL(16,6) NOT NULL',
            'ended_at' => 'DECIMAL(16,6) NULL',
            'duration_ms' => 'DECIMAL(10,3) NULL',
            'request_method' => 'VARCHAR(10) NULL',
            'request_path' => 'VARCHAR(512) NULL',
            'status_code' => 'INTEGER NULL',
            'status' => "VARCHAR(20) NOT NULL DEFAULT 'ok'",
            'span_count' => 'INTEGER NOT NULL DEFAULT 0',
            'query_count' => 'INTEGER NOT NULL DEFAULT 0',
            'error_count' => 'INTEGER NOT NULL DEFAULT 0',
            'slow_query_count' => 'INTEGER NOT NULL DEFAULT 0',
            'peak_memory_bytes' => 'INTEGER NULL',
            'sampled' => 'INTEGER NOT NULL DEFAULT 0',
            'spans' => 'TEXT NULL',
            'tags' => 'TEXT NULL',
            'user_id' => 'VARCHAR(36) NULL',
            'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ]);
        $this->execute($sql);

        $this->execute($this->createIndex('observability_traces', 'idx_obs_created_at', ['created_at']));
        $this->execute($this->createIndex('observability_traces', 'idx_obs_duration', ['duration_ms']));
        $this->execute($this->createIndex('observability_traces', 'idx_obs_status', ['status']));
        $this->execute($this->createIndex('observability_traces', 'idx_obs_status_code', ['status_code']));
        $this->execute($this->createIndex('observability_traces', 'idx_obs_sampled', ['sampled']));
        $this->execute($this->createIndex('observability_traces', 'idx_obs_request_path', ['request_path']));
    }

    public function down(): void
    {
        $this->execute($this->dropTable('observability_traces'));
    }
}
