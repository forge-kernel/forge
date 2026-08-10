<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Tests;

require_once __DIR__ . '/Concerns/SqliteDatabase.php';

use Modules\ForgeDatabaseSQL\DB\Migrator;
use Modules\ForgeDatabaseSQL\Tests\Concerns\SqliteDatabase;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Throwable;

#[Group("forgedatabase-migrator-sqlite")]
final class MigratorSqliteTest extends TestCase
{
    use SqliteDatabase;

    /**
     * Writes a temp migration file and returns its path. The migration class
     * name must be unique per test to avoid redeclaration errors.
     */
    private function writeTempMigration(string $classBody): string
    {
        $dir = sys_get_temp_dir() . '/forge_migrator_' . uniqid();
        mkdir($dir, 0777, true);
        $className = $this->extractClassName($classBody);
        $path = $dir . '/' . $className . '.php';
        file_put_contents(
            $path,
            "<?php\n\ndeclare(strict_types=1);\n\n"
            . "use Modules\\ForgeDatabaseSQL\\DB\\Migrations\\Migration;\n\n"
            . $classBody,
        );
        return $path;
    }

    private function extractClassName(string $classBody): string
    {
        preg_match('/class\s+(\w+)/', $classBody, $matches);
        return $matches[1];
    }

    #[Test("Migrator can DROP a table created by an earlier migration in the same batch on SQLite")]
    public function drop_table_created_in_same_batch(): void
    {
        $conn = $this->sqliteConnection();
        $migrator = new Migrator($conn);

        $create = $this->writeTempMigration(<<<'PHP'
final class MigLockUsersCreate extends Migration
{
    public function up(): void
    {
        $this->execute($this->createTable('users', [
            'id'   => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'name' => 'TEXT NOT NULL',
        ], true));
    }

    public function down(): void
    {
        $this->execute($this->dropTable('users'));
    }
}
PHP);
        $drop = $this->writeTempMigration(<<<'PHP'
final class MigLockUsersDrop extends Migration
{
    public function up(): void
    {
        $this->execute($this->dropTable('users'));
    }
}
PHP);

        $batch = (new ReflectionMethod(
            Migrator::class,
            'getNextBatchNumber',
        ))->invoke($migrator);
        (new ReflectionProperty(
            Migrator::class,
            'currentBatch',
        ))->setValue($migrator, $batch);

        $conn->beginTransaction();
        try {
            (new ReflectionMethod(Migrator::class, 'runMigration'))->invoke(
                $migrator,
                $create,
            );
            (new ReflectionMethod(Migrator::class, 'runMigration'))->invoke(
                $migrator,
                $drop,
            );
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        $this->assertFalse($this->tableExists($conn, 'users'));

        $stmt = $conn->query(
            "SELECT COUNT(*) FROM forge_migrations",
        );
        $count = (int) $stmt->fetchColumn();
        $stmt->closeCursor();
        $this->assertSame(2, $count);

        @unlink($create);
        @unlink($drop);
    }

    #[Test("getRanMigrations with filters leaves no read lock behind")]
    public function get_ran_migrations_filtered_leaves_no_lock(): void
    {
        $conn = $this->sqliteConnection();
        $migrator = new Migrator($conn);

        $stmt = $conn->prepare(
            "INSERT INTO forge_migrations (migration, batch, type, module, migration_group)
             VALUES (?, ?, ?, ?, ?)",
        );
        $stmt->execute(['2026_01_01_000001_MigLockUsersCreate.php', 1, 'app', null, null]);
        $stmt->closeCursor();

        $ran = $migrator->getRanMigrations(1, 'app', null, null);
        $this->assertCount(1, $ran);
        $this->assertSame(
            ['2026_01_01_000001_MigLockUsersCreate.php'],
            $ran,
        );

        $this->assertCanDropTableInsideTransaction($conn, 'tmp_drop');
    }
}
