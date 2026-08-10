<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Tests;

require_once __DIR__ . '/Concerns/SqliteDatabase.php';

use Modules\ForgeDatabaseSQL\Services\MigrationBatchService;
use Modules\ForgeDatabaseSQL\Tests\Concerns\SqliteDatabase;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;

#[Group("forgedatabase-batch")]
final class MigrationBatchServiceTest extends TestCase
{
    use SqliteDatabase;

    private function seedMigrations($conn): void
    {
        $conn->exec(
            "CREATE TABLE forge_migrations (
                migration VARCHAR(255) PRIMARY KEY,
                batch INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                module VARCHAR(255) NULL,
                migration_group VARCHAR(255) NULL
            )",
        );

        $stmt = $conn->prepare(
            "INSERT INTO forge_migrations (migration, batch, type, module, migration_group)
             VALUES (?, ?, ?, ?, ?)",
        );
        $stmt->execute(['m1.php', 1, 'app', null, null]);
        $stmt->execute(['m2.php', 1, 'app', null, null]);
        $stmt->execute(['m3.php', 2, 'module', 'Blog', null]);
        $stmt->closeCursor();
    }

    #[Test("batch read queries leave no SQLite read lock behind")]
    public function batch_reads_do_not_block_drop(): void
    {
        $conn = $this->sqliteConnection();
        $this->seedMigrations($conn);
        $service = new MigrationBatchService($conn);

        $this->assertSame(2, $service->getLastBatch());
        $this->assertSame(3, $service->getNextBatch());
        $this->assertSame(1, $service->getFirstBatch());
        $this->assertSame(2, $service->getTotalBatches());
        $this->assertTrue($service->batchExists(1));
        $this->assertFalse($service->batchExists(5));
        $this->assertSame(2, $service->countMigrationsInBatch(1));
        $this->assertSame([2, 1], $service->getAllBatches());
        $this->assertSame([2], $service->getBatchesForRollback(1));

        $this->assertCanDropTableInsideTransaction($conn, 'tmp_drop');
    }

    #[Test("getMigrationsToRollback with filters leaves no read lock behind")]
    public function migrations_to_rollback_filtered_no_lock(): void
    {
        $conn = $this->sqliteConnection();
        $this->seedMigrations($conn);
        $service = new MigrationBatchService($conn);

        $migrations = $service->getMigrationsToRollback(1, ['type' => 'module']);
        $this->assertCount(1, $migrations);
        $this->assertSame('m3.php', $migrations[0]['migration']);

        $this->assertCanDropTableInsideTransaction($conn, 'tmp_drop');
    }
}
