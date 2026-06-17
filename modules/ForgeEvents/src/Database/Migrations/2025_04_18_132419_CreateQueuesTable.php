<?php

declare(strict_types=1);


use App\Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Timestamps;
use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'queues')]
#[Table(name: 'queue_jobs')]
#[Index(columns: ['queue', 'process_at'], name: 'idx_queue_process_at')]
#[Index(columns: ['attempts'], name: 'idx_attempts')]
#[Timestamps]
class CreateQueuesTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::INTEGER, primaryKey: true, autoIncrement: true)]
    public readonly string $id;

    #[Column(name: 'queue', type: ColumnType::STRING, nullable: false, default: 'default', length: 255)]
    public readonly string $queue;

    #[Column(name: 'payload', type: ColumnType::TEXT, nullable: false)]
    public readonly string $payload;

    #[Column(name: 'attempts', type: ColumnType::INTEGER, nullable: true, default: 0)]
    public readonly int $attempts;

    #[Column(name: 'max_retries', type: ColumnType::INTEGER, nullable: true, default: 1)]
    public readonly int $max_retries;

    #[Column(name: 'priority', type: ColumnType::INTEGER, nullable: true, default: 100)]
    public readonly int $priority;

    #[Column(name: 'process_at', type: ColumnType::TIMESTAMP, nullable: true, default: null)]
    public readonly ?string $process_at;

    #[Column(name: 'reserved_at', type: ColumnType::TIMESTAMP, nullable: true, default: null)]
    public readonly ?string $reserved_at;

    #[Column(name: 'failed_at', type: ColumnType::TIMESTAMP, nullable: true, default: null)]
    public readonly ?string $failed_at;
}
