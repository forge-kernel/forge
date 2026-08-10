<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Tests;

require_once __DIR__ . '/Concerns/SqliteDatabase.php';

use Modules\ForgeDatabaseSQL\DB\Seeders\SeederManager;
use Modules\ForgeDatabaseSQL\Tests\Concerns\SqliteDatabase;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use ReflectionMethod;

#[Group("forgedatabase-seeder")]
final class SeederManagerTest extends TestCase
{
    use SqliteDatabase;

    #[Test("seeder batch read queries leave no SQLite read lock behind")]
    public function seeder_reads_do_not_block_drop(): void
    {
        $conn = $this->sqliteConnection();
        $manager = new SeederManager($conn);

        $this->assertSame([], $manager->getRanSeeders(1));
        $this->assertSame([], $manager->getSeedersForRollback(1));
        $this->assertSame(
            1,
            (new ReflectionMethod(
                SeederManager::class,
                'getNextBatchNumber',
            ))->invoke($manager),
        );

        $stmt = $conn->prepare(
            "INSERT INTO forge_seeders (seeder, batch) VALUES (?, ?)",
        );
        $stmt->execute(['s1.php', 1]);
        $stmt->closeCursor();

        $this->assertSame(['s1.php'], $manager->getRanSeeders(1));
        $this->assertSame(
            ['s1.php'],
            array_keys($manager->getAllRanSeedersWithDetails()),
        );

        $this->assertCanDropTableInsideTransaction($conn, 'tmp_drop');
    }
}
