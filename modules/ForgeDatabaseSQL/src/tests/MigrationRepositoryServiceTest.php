<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Tests;

require_once __DIR__ . '/Concerns/SqliteDatabase.php';

use Modules\ForgeDatabaseSQL\Services\MigrationRepositoryService;
use Modules\ForgeDatabaseSQL\Tests\Concerns\SqliteDatabase;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;

#[Group("forgedatabase-repository")]
final class MigrationRepositoryServiceTest extends TestCase
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

    #[Test("repository read queries leave no SQLite read lock behind")]
    public function repository_reads_do_not_block_drop(): void
    {
        $conn = $this->sqliteConnection();
        $this->seedMigrations($conn);
        $repository = new MigrationRepositoryService($conn);

        $this->assertSame(2, $repository->getLastBatch());
        $this->assertSame(3, $repository->getNextBatch());
        $this->assertTrue($repository->hasMigration('m1.php'));
        $this->assertFalse($repository->hasMigration('missing.php'));
        $this->assertSame('app', $repository->getMigrationMetadata('m1.php')['type']);
        $this->assertNull($repository->getMigrationMetadata('missing.php'));
        $this->assertSame(3, $repository->countMigrationsByType());
        $this->assertSame(2, $repository->countMigrationsByType('app'));
        $this->assertSame(['m3.php'], $repository->getMigrationsForRollback(1, 'module'));

        $this->assertCanDropTableInsideTransaction($conn, 'tmp_drop');
    }
}
