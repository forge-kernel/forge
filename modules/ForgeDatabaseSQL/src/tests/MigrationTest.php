<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Tests;

use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;
use App\Modules\ForgeDatabaseSQL\DB\Schema\FormatterInterface;
use App\Modules\ForgeDatabaseSQL\DB\Schema\MySqlFormatter;
use App\Modules\ForgeDatabaseSQL\DB\Schema\PostgreSqlFormatter;
use App\Modules\ForgeDatabaseSQL\DB\Schema\SqliteFormatter;
use App\Modules\ForgeTesting\Attributes\BeforeEach;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use PDO;

#[Group("forgedatabase-migration")]
final class MigrationTest extends TestCase
{
    private function createMigration(string $driver, FormatterInterface $formatter): Migration
    {
        $conn = new class($driver) implements DatabaseConnectionInterface {
            public function __construct(private string $driver) {}
            public function getPdo(): PDO { throw new \RuntimeException('not needed'); }
            public function exec(string $statement): int|false { return false; }
            public function prepare(string $statement): \PDOStatement { throw new \RuntimeException('not needed'); }
            public function query(string $statement): \PDOStatement { throw new \RuntimeException('not needed'); }
            public function beginTransaction(): bool { return true; }
            public function commit(): bool { return true; }
            public function rollBack(): bool { return true; }
            public function getDriver(): string { return $this->driver; }
        };

        return new class($conn, $formatter) extends Migration {};
    }

    // ─── createTable ───

    #[Test("createTable generates CREATE TABLE SQL for MySQL")]
    public function create_table_mysql(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->createTable('users', ['id' => 'INTEGER PRIMARY KEY AUTO_INCREMENT', 'name' => 'VARCHAR(255) NOT NULL']);
        $this->assertStringContainsString('CREATE TABLE `users`', $sql);
        $this->assertStringContainsString('`id` INT PRIMARY KEY AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('`name` VARCHAR(255) NOT NULL', $sql);
        $this->assertStringContainsString('ENGINE=InnoDB', $sql);
    }

    #[Test("createTable generates CREATE TABLE SQL for SQLite")]
    public function create_table_sqlite(): void
    {
        $m = $this->createMigration('sqlite', new SqliteFormatter());
        $sql = $m->createTable('users', ['id' => 'INTEGER PRIMARY KEY AUTOINCREMENT', 'name' => 'TEXT NOT NULL']);
        $this->assertStringContainsString('CREATE TABLE "users"', $sql);
        $this->assertStringContainsString('"id" INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $this->assertStringContainsString('"name" TEXT NOT NULL', $sql);
    }

    #[Test("createTable generates CREATE TABLE SQL for PostgreSQL")]
    public function create_table_pgsql(): void
    {
        $m = $this->createMigration('pgsql', new PostgreSqlFormatter());
        $sql = $m->createTable('users', ['id' => 'SERIAL PRIMARY KEY', 'name' => 'VARCHAR(255) NOT NULL']);
        $this->assertStringContainsString('CREATE TABLE "users"', $sql);
        $this->assertStringContainsString('"id" SERIAL PRIMARY KEY', $sql);
        $this->assertStringContainsString('"name" VARCHAR(255) NOT NULL', $sql);
    }

    #[Test("createTable with IF NOT EXISTS")]
    public function create_table_if_not_exists(): void
    {
        $m = $this->createMigration('sqlite', new SqliteFormatter());
        $sql = $m->createTable('users', ['id' => 'INTEGER PRIMARY KEY'], true);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS', $sql);
    }

    // ─── dropTable ───

    #[Test("dropTable generates DROP TABLE SQL for MySQL")]
    public function drop_table_mysql(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $this->assertSame('DROP TABLE `users`', $m->dropTable('users'));
    }

    #[Test("dropTable generates DROP TABLE SQL for SQLite")]
    public function drop_table_sqlite(): void
    {
        $m = $this->createMigration('sqlite', new SqliteFormatter());
        $this->assertSame('DROP TABLE "users"', $m->dropTable('users'));
    }

    // ─── createIndex ───

    #[Test("createIndex generates CREATE INDEX SQL")]
    public function create_index(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->createIndex('users', 'idx_name', ['name']);
        $this->assertStringContainsString('CREATE INDEX IF NOT EXISTS `idx_name` ON `users` (`name`)', $sql);
    }

    #[Test("createIndex with unique")]
    public function create_index_unique(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->createIndex('users', 'idx_email', ['email'], true);
        $this->assertStringContainsString('CREATE UNIQUE INDEX IF NOT EXISTS', $sql);
    }

    #[Test("createIndex with multiple columns")]
    public function create_index_multi_column(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->createIndex('orders', 'idx_user_date', ['user_id', 'created_at']);
        $this->assertStringContainsString('(`user_id`, `created_at`)', $sql);
    }

    // ─── addForeignKey ───

    #[Test("addForeignKey generates ALTER TABLE for MySQL")]
    public function add_foreign_key_mysql(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->addForeignKey('posts', 'user_id', 'users');
        $this->assertSame('ALTER TABLE `posts` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE', $sql);
    }

    #[Test("addForeignKey with custom onDelete")]
    public function add_foreign_key_on_delete(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->addForeignKey('posts', 'user_id', 'users', 'id', 'SET NULL');
        $this->assertStringContainsString('ON DELETE SET NULL', $sql);
    }

    #[Test("addForeignKey returns null for SQLite")]
    public function add_foreign_key_sqlite(): void
    {
        $m = $this->createMigration('sqlite', new SqliteFormatter());
        $this->assertNull($m->addForeignKey('posts', 'user_id', 'users'));
    }

    #[Test("addForeignKey with custom referenced column")]
    public function add_foreign_key_custom_ref(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->addForeignKey('comments', 'parent_id', 'comments', 'id', 'CASCADE');
        $this->assertStringContainsString('REFERENCES `comments`(`id`)', $sql);
    }

    // ─── addColumn ───

    #[Test("addColumn generates ALTER TABLE ADD COLUMN for MySQL")]
    public function add_column_mysql(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->addColumn('users', 'email', 'VARCHAR(255)', false, null);
        $this->assertStringContainsString('ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255) NOT NULL', $sql);
    }

    #[Test("addColumn nullable with default NULL")]
    public function add_column_nullable_default(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->addColumn('users', 'bio', 'TEXT', true, null);
        $this->assertStringContainsString('NULL DEFAULT NULL', $sql);
    }

    #[Test("addColumn with AFTER for MySQL")]
    public function add_column_after(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->addColumn('users', 'email', 'VARCHAR(255)', true, null, 'name');
        $this->assertStringContainsString('AFTER `name`', $sql);
    }

    #[Test("addColumn with FIRST for MySQL")]
    public function add_column_first(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->addColumn('users', 'id', 'INTEGER', false, null, null, true);
        $this->assertStringContainsString('FIRST', $sql);
    }

    #[Test("addColumn for SQLite ignores AFTER/FIRST")]
    public function add_column_sqlite_no_position(): void
    {
        $m = $this->createMigration('sqlite', new SqliteFormatter());
        $sql = $m->addColumn('users', 'email', 'TEXT', true, null, 'name');
        $this->assertStringNotContainsString('AFTER', $sql);
        $this->assertStringNotContainsString('FIRST', $sql);
        $this->assertStringContainsString('ALTER TABLE "users" ADD COLUMN "email" TEXT NULL', $sql);
    }

    // ─── dropColumn ───

    #[Test("dropColumn for MySQL generates ALTER TABLE DROP COLUMN")]
    public function drop_column_mysql(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->dropColumn('users', 'old_col');
        $this->assertSame('ALTER TABLE `users` DROP COLUMN `old_col`', $sql);
    }

    #[Test("dropColumn for SQLite returns null")]
    public function drop_column_sqlite(): void
    {
        $m = $this->createMigration('sqlite', new SqliteFormatter());
        $this->assertNull($m->dropColumn('users', 'col'));
    }

    // ─── renameColumn ───

    #[Test("renameColumn for MySQL")]
    public function rename_column_mysql(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->renameColumn('users', 'old_name', 'new_name');
        $this->assertSame('ALTER TABLE `users` RENAME COLUMN `old_name` TO `new_name`', $sql);
    }

    #[Test("renameColumn for SQLite")]
    public function rename_column_sqlite(): void
    {
        $m = $this->createMigration('sqlite', new SqliteFormatter());
        $sql = $m->renameColumn('users', 'old_name', 'new_name');
        $this->assertSame('ALTER TABLE "users" RENAME COLUMN "old_name" TO "new_name"', $sql);
    }

    #[Test("renameColumn for PostgreSQL")]
    public function rename_column_pgsql(): void
    {
        $m = $this->createMigration('pgsql', new PostgreSqlFormatter());
        $sql = $m->renameColumn('users', 'old_name', 'new_name');
        $this->assertSame('ALTER TABLE "users" RENAME COLUMN "old_name" TO "new_name"', $sql);
    }

    // ─── changeColumn ───

    #[Test("changeColumn for MySQL uses MODIFY COLUMN")]
    public function change_column_mysql(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->changeColumn('users', 'email', 'VARCHAR(100)', false);
        $this->assertStringContainsString('ALTER TABLE `users` MODIFY COLUMN `email` VARCHAR(100) NOT NULL', $sql);
    }

    #[Test("changeColumn for PostgreSQL uses ALTER COLUMN SET DATA TYPE")]
    public function change_column_pgsql(): void
    {
        $m = $this->createMigration('pgsql', new PostgreSqlFormatter());
        $sql = $m->changeColumn('users', 'email', 'VARCHAR(200)', true);
        $this->assertStringContainsString('ALTER TABLE "users" ALTER COLUMN "email" SET DATA TYPE VARCHAR(200)', $sql);
    }

    #[Test("changeColumn for SQLite returns null")]
    public function change_column_sqlite(): void
    {
        $m = $this->createMigration('sqlite', new SqliteFormatter());
        $this->assertNull($m->changeColumn('users', 'email', 'TEXT'));
    }

    #[Test("changeColumn with default value")]
    public function change_column_with_default(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $sql = $m->changeColumn('users', 'name', 'VARCHAR(150)', true, 'Anonymous');
        $this->assertStringContainsString("DEFAULT 'Anonymous'", $sql);
    }

    // ─── renameTable ───

    #[Test("renameTable for MySQL")]
    public function rename_table_mysql(): void
    {
        $m = $this->createMigration('mysql', new MySqlFormatter());
        $this->assertSame('ALTER TABLE `old_name` RENAME TO `new_name`', $m->renameTable('old_name', 'new_name'));
    }

    #[Test("renameTable for SQLite")]
    public function rename_table_sqlite(): void
    {
        $m = $this->createMigration('sqlite', new SqliteFormatter());
        $this->assertSame('ALTER TABLE "old_name" RENAME TO "new_name"', $m->renameTable('old_name', 'new_name'));
    }

    #[Test("renameTable for PostgreSQL")]
    public function rename_table_pgsql(): void
    {
        $m = $this->createMigration('pgsql', new PostgreSqlFormatter());
        $this->assertSame('ALTER TABLE "old_name" RENAME TO "new_name"', $m->renameTable('old_name', 'new_name'));
    }
}
