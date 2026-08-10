<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Tests;

require_once __DIR__ . '/Concerns/SqliteDatabase.php';

use Modules\ForgeDatabaseSQL\Services\MigrationTableManagerService;
use Modules\ForgeDatabaseSQL\Tests\Concerns\SqliteDatabase;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;

#[Group("forgedatabase-table-manager")]
final class MigrationTableManagerServiceTest extends TestCase
{
    use SqliteDatabase;

    #[Test("migrationsTableExists leaves no lock that blocks dropMigrationsTable")]
    public function migrations_table_drop_after_exists_check(): void
    {
        $conn = $this->sqliteConnection();
        $service = new MigrationTableManagerService($conn);

        $this->assertTrue($service->createMigrationsTable());
        $this->assertTrue($service->migrationsTableExists());
        $this->assertTrue($service->validateTableSchema());

        $this->assertTrue($service->dropMigrationsTable());
        $this->assertFalse($service->migrationsTableExists());
    }
}
