<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Tests;

use App\Modules\ForgeDatabaseSQL\DB\Schema\MySqlFormatter;
use App\Modules\ForgeDatabaseSQL\DB\Schema\SqliteFormatter;
use App\Modules\ForgeDatabaseSQL\DB\Schema\PostgreSqlFormatter;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;

#[Group("forgedatabase-formatters")]
final class FormatterTest extends TestCase
{
    // ──────────────── MySqlFormatter ────────────────

    #[Test("MySQL: basic column types map correctly")]
    public function mysql_format_column_types(): void
    {
        $f = new MySqlFormatter();
        $this->assertStringContainsString('VARCHAR(255)', $f->formatColumn('name', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('VARCHAR(50)', $f->formatColumn('code', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'length' => 50]));
        $this->assertStringContainsString('INT', $f->formatColumn('age', ['type' => 'INTEGER', 'nullable' => true, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('TEXT', $f->formatColumn('body', ['type' => 'TEXT', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('BOOLEAN', $f->formatColumn('active', ['type' => 'BOOLEAN', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('FLOAT', $f->formatColumn('price', ['type' => 'FLOAT', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('DATE', $f->formatColumn('born', ['type' => 'DATE', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('DATETIME', $f->formatColumn('created', ['type' => 'DATETIME', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('TIMESTAMP', $f->formatColumn('updated', ['type' => 'TIMESTAMP', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('CHAR(36)', $f->formatColumn('id', ['type' => 'UUID', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('JSON', $f->formatColumn('meta', ['type' => 'JSON', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('BLOB', $f->formatColumn('data', ['type' => 'BLOB', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
    }

    #[Test("MySQL: INTEGER primary key with autoIncrement includes AUTO_INCREMENT")]
    public function mysql_auto_increment(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatColumn('id', ['type' => 'INTEGER', 'nullable' => false, 'primary' => true, 'unique' => false, 'autoIncrement' => true]);
        $this->assertStringContainsString('AUTO_INCREMENT', $result);
        $this->assertStringContainsString('PRIMARY KEY', $result);
    }

    #[Test("MySQL: INTEGER primary key without autoIncrement omits AUTO_INCREMENT")]
    public function mysql_primary_key_no_auto_increment(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatColumn('id', ['type' => 'INTEGER', 'nullable' => false, 'primary' => true, 'unique' => false, 'autoIncrement' => false]);
        $this->assertStringNotContainsString('AUTO_INCREMENT', $result);
        $this->assertStringContainsString('PRIMARY KEY', $result);
    }

    #[Test("MySQL: ENUM with values generates ENUM(...)")]
    public function mysql_enum_with_values(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatColumn('status', ['type' => 'ENUM', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'enum' => ['active', 'inactive']]);
        $this->assertStringContainsString("ENUM('active','inactive')", $result);
    }

    #[Test("MySQL: ENUM without values falls back to VARCHAR(255)")]
    public function mysql_enum_without_values(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatColumn('status', ['type' => 'ENUM', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]);
        $this->assertStringContainsString('VARCHAR(255)', $result);
    }

    #[Test("MySQL: COMMENT is escaped for single quotes")]
    public function mysql_comment_escaping(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatColumn('name', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'comment' => "user's name"]);
        $this->assertStringContainsString("COMMENT 'user\\'s name'", $result);
    }

    #[Test("MySQL: nullable outputs NULL, non-nullable outputs NOT NULL")]
    public function mysql_nullable(): void
    {
        $f = new MySqlFormatter();
        $nullResult = $f->formatColumn('col', ['type' => 'STRING', 'nullable' => true, 'primary' => false, 'unique' => false, 'autoIncrement' => false]);
        $this->assertStringContainsString('NULL', $nullResult);
        $notNullResult = $f->formatColumn('col', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]);
        $this->assertStringContainsString('NOT NULL', $notNullResult);
    }

    #[Test("MySQL: UNSIGNED is included when set")]
    public function mysql_unsigned(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatColumn('count', ['type' => 'INTEGER', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'unsigned' => true]);
        $this->assertStringContainsString('UNSIGNED', $result);
    }

    #[Test("MySQL: CHECK constraint is included")]
    public function mysql_check(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatColumn('age', ['type' => 'INTEGER', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'check' => 'age >= 0']);
        $this->assertStringContainsString('CHECK (age >= 0)', $result);
    }

    #[Test("MySQL: formatTableOptions returns engine and charset")]
    public function mysql_table_options(): void
    {
        $f = new MySqlFormatter();
        $options = $f->formatTableOptions();
        $this->assertStringContainsString('ENGINE=InnoDB', $options);
        $this->assertStringContainsString('utf8mb4', $options);
    }

    #[Test("MySQL: formatIndex with unique")]
    public function mysql_index_unique(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatIndex(['name' => 'idx_name', 'columns' => ['name'], 'unique' => true, 'table' => 'users']);
        $this->assertStringContainsString('CREATE UNIQUE INDEX', $result);
        $this->assertStringContainsString('`idx_name`', $result);
        $this->assertStringContainsString('`users`', $result);
    }

    #[Test("MySQL: formatIndex without unique")]
    public function mysql_index_non_unique(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatIndex(['name' => 'idx_name', 'columns' => ['name'], 'unique' => false, 'table' => 'users']);
        $this->assertStringContainsString('CREATE INDEX', $result);
        $this->assertStringNotContainsString('UNIQUE', $result);
    }

    #[Test("MySQL: formatAddColumn with AFTER")]
    public function mysql_add_column_after(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatAddColumn('users', 'email', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false], 'name');
        $this->assertStringContainsString('ALTER TABLE `users` ADD COLUMN', $result);
        $this->assertStringContainsString('AFTER `name`', $result);
    }

    #[Test("MySQL: formatAddColumn with FIRST")]
    public function mysql_add_column_first(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatAddColumn('users', 'id', ['type' => 'INTEGER', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false], null, true);
        $this->assertStringContainsString('FIRST', $result);
    }

    #[Test("MySQL: formatDropColumn")]
    public function mysql_drop_column(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatDropColumn('users', 'old_col');
        $this->assertSame('ALTER TABLE `users` DROP COLUMN `old_col`', $result);
    }

    #[Test("MySQL: formatRenameColumn")]
    public function mysql_rename_column(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatRenameColumn('users', 'old_name', 'new_name');
        $this->assertSame('ALTER TABLE `users` RENAME COLUMN `old_name` TO `new_name`', $result);
    }

    #[Test("MySQL: formatAlterColumn uses MODIFY COLUMN")]
    public function mysql_alter_column(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatAlterColumn('users', 'email', ['type' => 'STRING', 'nullable' => true, 'primary' => false, 'unique' => false, 'autoIncrement' => false]);
        $this->assertStringContainsString('ALTER TABLE `users` MODIFY COLUMN', $result);
    }

    #[Test("MySQL: formatDefault with boolean true outputs DEFAULT 1")]
    public function mysql_default_boolean_true(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatColumn('active', ['type' => 'BOOLEAN', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'default' => true]);
        $this->assertStringContainsString('DEFAULT 1', $result);
    }

    #[Test("MySQL: formatDefault with boolean false outputs DEFAULT 0")]
    public function mysql_default_boolean_false(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatColumn('active', ['type' => 'BOOLEAN', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'default' => false]);
        $this->assertStringContainsString('DEFAULT 0', $result);
    }

    #[Test("MySQL: formatDefault with CURRENT_TIMESTAMP")]
    public function mysql_default_current_timestamp(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatColumn('created', ['type' => 'TIMESTAMP', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'default' => 'CURRENT_TIMESTAMP']);
        $this->assertStringContainsString('DEFAULT CURRENT_TIMESTAMP', $result);
    }

    #[Test("MySQL: relationships generate correct ALTER TABLE SQL")]
    public function mysql_relationships(): void
    {
        $f = new MySqlFormatter();
        $f->addRelationship('belongsTo', ['foreignKey' => 'user_id', 'relatedTable' => 'users', 'onDelete' => 'CASCADE']);
        $sql = $f->formatRelationships('posts');
        $this->assertStringContainsString('ALTER TABLE `posts` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(id) ON DELETE CASCADE', $sql);
    }

    #[Test("MySQL: manyToMany generates junction table")]
    public function mysql_many_to_many(): void
    {
        $f = new MySqlFormatter();
        $f->addRelationship('manyToMany', ['joinTable' => 'post_tag', 'foreignKey' => 'post_id', 'relatedKey' => 'tag_id', 'sourceTable' => 'posts', 'relatedTable' => 'tags']);
        $sql = $f->formatRelationships('posts');
        $this->assertStringContainsString('CREATE TABLE `post_tag`', $sql);
        $this->assertStringContainsString('`post_id` INT UNSIGNED NOT NULL', $sql);
        $this->assertStringContainsString('`tag_id` INT UNSIGNED NOT NULL', $sql);
    }

    #[Test("MySQL: formatInteger with unsigned")]
    public function mysql_integer_unsigned(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatColumn('count', ['type' => 'INTEGER', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'unsigned' => true]);
        $this->assertStringContainsString('INT UNSIGNED', $result);
    }

    #[Test("MySQL: formatInteger without unsigned")]
    public function mysql_integer_without_unsigned(): void
    {
        $f = new MySqlFormatter();
        $result = $f->formatColumn('count', ['type' => 'INTEGER', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]);
        $this->assertStringContainsString('INT', $result);
        $this->assertStringNotContainsString('UNSIGNED', $result);
    }

    // ──────────────── SqliteFormatter ────────────────

    #[Test("SQLite: basic column types map correctly")]
    public function sqlite_format_column_types(): void
    {
        $f = new SqliteFormatter();
        $this->assertStringContainsString('TEXT', $f->formatColumn('name', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('INTEGER', $f->formatColumn('age', ['type' => 'INTEGER', 'nullable' => true, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('INTEGER', $f->formatColumn('active', ['type' => 'BOOLEAN', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('REAL', $f->formatColumn('price', ['type' => 'FLOAT', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('NUMERIC', $f->formatColumn('amount', ['type' => 'DECIMAL', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('TEXT', $f->formatColumn('born', ['type' => 'DATE', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('TEXT', $f->formatColumn('uuid', ['type' => 'UUID', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('BLOB', $f->formatColumn('data', ['type' => 'BLOB', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('JSON', $f->formatColumn('meta', ['type' => 'JSON', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
    }

    #[Test("SQLite: INTEGER primary key with autoIncrement includes AUTOINCREMENT")]
    public function sqlite_auto_increment(): void
    {
        $f = new SqliteFormatter();
        $result = $f->formatColumn('id', ['type' => 'INTEGER', 'nullable' => false, 'primary' => true, 'unique' => false, 'autoIncrement' => true]);
        $this->assertStringContainsString('AUTOINCREMENT', $result);
        $this->assertStringContainsString('PRIMARY KEY', $result);
    }

    #[Test("SQLite: ENUM with values generates CHECK constraint")]
    public function sqlite_enum_with_values(): void
    {
        $f = new SqliteFormatter();
        $result = $f->formatColumn('status', ['type' => 'ENUM', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'enum' => ['active', 'inactive']]);
        $this->assertStringContainsString('TEXT', $result);
        $this->assertStringContainsString('CHECK ("status" IN (\'active\',\'inactive\'))', $result);
    }

    #[Test("SQLite: ENUM without values falls back to TEXT")]
    public function sqlite_enum_without_values(): void
    {
        $f = new SqliteFormatter();
        $result = $f->formatColumn('status', ['type' => 'ENUM', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]);
        $this->assertSame('"status" TEXT NOT NULL', $result);
    }

    #[Test("SQLite: formatIndex includes IF NOT EXISTS")]
    public function sqlite_index(): void
    {
        $f = new SqliteFormatter();
        $result = $f->formatIndex(['name' => 'idx_name', 'columns' => ['name'], 'unique' => false, 'table' => 'users']);
        $this->assertStringContainsString('CREATE INDEX IF NOT EXISTS', $result);
    }

    #[Test("SQLite: formatAddColumn ignores AFTER/FIRST")]
    public function sqlite_add_column(): void
    {
        $f = new SqliteFormatter();
        $result = $f->formatAddColumn('users', 'email', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false], 'name');
        $this->assertStringNotContainsString('AFTER', $result);
        $this->assertStringNotContainsString('FIRST', $result);
        $this->assertStringContainsString('ALTER TABLE "users" ADD COLUMN', $result);
    }

    #[Test("SQLite: formatDropColumn returns null")]
    public function sqlite_drop_column(): void
    {
        $f = new SqliteFormatter();
        $this->assertNull($f->formatDropColumn('users', 'col'));
    }

    #[Test("SQLite: formatAlterColumn returns null")]
    public function sqlite_alter_column(): void
    {
        $f = new SqliteFormatter();
        $this->assertNull($f->formatAlterColumn('users', 'col', ['type' => 'STRING', 'nullable' => true, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
    }

    #[Test("SQLite: formatTableOptions returns empty string")]
    public function sqlite_table_options(): void
    {
        $f = new SqliteFormatter();
        $this->assertSame('', $f->formatTableOptions());
    }

    #[Test("SQLite: formatRelationships returns empty when skipForeignKeys is true")]
    public function sqlite_relationships_skipped(): void
    {
        $f = new SqliteFormatter();
        $f->addRelationship('belongsTo', ['foreignKey' => 'user_id', 'relatedTable' => 'users', 'onDelete' => 'CASCADE']);
        $this->assertSame('', $f->formatRelationships('posts'));
    }

    #[Test("SQLite: formatRenameColumn generates valid SQL")]
    public function sqlite_rename_column(): void
    {
        $f = new SqliteFormatter();
        $result = $f->formatRenameColumn('users', 'old', 'new');
        $this->assertSame('ALTER TABLE "users" RENAME COLUMN "old" TO "new"', $result);
    }

    #[Test("SQLite: uses double quotes for identifiers")]
    public function sqlite_identifier_quotes(): void
    {
        $f = new SqliteFormatter();
        $result = $f->formatColumn('name', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]);
        $this->assertStringContainsString('"name"', $result);
    }

    // ──────────────── PostgreSqlFormatter ────────────────

    #[Test("PostgreSQL: basic column types map correctly")]
    public function pgsql_format_column_types(): void
    {
        $f = new PostgreSqlFormatter();
        $this->assertStringContainsString('VARCHAR(255)', $f->formatColumn('name', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('VARCHAR(50)', $f->formatColumn('code', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'length' => 50]));
        $this->assertStringContainsString('INTEGER', $f->formatColumn('age', ['type' => 'INTEGER', 'nullable' => true, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('TEXT', $f->formatColumn('body', ['type' => 'TEXT', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('BOOLEAN', $f->formatColumn('active', ['type' => 'BOOLEAN', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('REAL', $f->formatColumn('price', ['type' => 'FLOAT', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('DATE', $f->formatColumn('born', ['type' => 'DATE', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('TIMESTAMP', $f->formatColumn('created', ['type' => 'DATETIME', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('UUID', $f->formatColumn('id', ['type' => 'UUID', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('JSONB', $f->formatColumn('meta', ['type' => 'JSON', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('BYTEA', $f->formatColumn('data', ['type' => 'BLOB', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
        $this->assertStringContainsString('TEXT[]', $f->formatColumn('tags', ['type' => 'ARRAY', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]));
    }

    #[Test("PostgreSQL: ENUM with values generates CHECK constraint")]
    public function pgsql_enum_with_values(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatColumn('status', ['type' => 'ENUM', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'enum' => ['active', 'inactive']]);
        $this->assertStringContainsString('VARCHAR(255)', $result);
        $this->assertStringContainsString('CHECK ("status" IN (\'active\',\'inactive\'))', $result);
    }

    #[Test("PostgreSQL: ENUM without values falls back to VARCHAR(255)")]
    public function pgsql_enum_without_values(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatColumn('status', ['type' => 'ENUM', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]);
        $this->assertSame('"status" VARCHAR(255) NOT NULL', $result);
    }

    #[Test("PostgreSQL: DECIMAL uses precision and scale")]
    public function pgsql_decimal(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatColumn('amount', ['type' => 'DECIMAL', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'precision' => 12, 'scale' => 4]);
        $this->assertStringContainsString('DECIMAL(12, 4)', $result);
    }

    #[Test("PostgreSQL: formatIndex includes IF NOT EXISTS")]
    public function pgsql_index(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatIndex(['name' => 'idx_name', 'columns' => ['name'], 'unique' => true, 'table' => 'users']);
        $this->assertStringContainsString('CREATE UNIQUE INDEX IF NOT EXISTS', $result);
    }

    #[Test("PostgreSQL: formatAddColumn ignores AFTER/FIRST")]
    public function pgsql_add_column(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatAddColumn('users', 'email', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false], 'name');
        $this->assertStringNotContainsString('AFTER', $result);
        $this->assertStringNotContainsString('FIRST', $result);
        $this->assertStringContainsString('ALTER TABLE "users" ADD COLUMN', $result);
    }

    #[Test("PostgreSQL: formatDropColumn generates ALTER TABLE DROP COLUMN")]
    public function pgsql_drop_column(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatDropColumn('users', 'old_col');
        $this->assertSame('ALTER TABLE "users" DROP COLUMN "old_col"', $result);
    }

    #[Test("PostgreSQL: formatRenameColumn")]
    public function pgsql_rename_column(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatRenameColumn('users', 'old', 'new');
        $this->assertSame('ALTER TABLE "users" RENAME COLUMN "old" TO "new"', $result);
    }

    #[Test("PostgreSQL: formatAlterColumn with type change")]
    public function pgsql_alter_column_type(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatAlterColumn('users', 'email', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false]);
        $this->assertStringContainsString('ALTER TABLE "users" ALTER COLUMN "email" SET DATA TYPE VARCHAR(255)', $result);
    }

    #[Test("PostgreSQL: formatAlterColumn with nullable change")]
    public function pgsql_alter_column_nullable(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatAlterColumn('users', 'email', ['nullable' => true]);
        $this->assertStringContainsString('ALTER COLUMN "email" DROP NOT NULL', $result);
    }

    #[Test("PostgreSQL: formatAlterColumn with default drop")]
    public function pgsql_alter_column_drop_default(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatAlterColumn('users', 'status', ['default' => null]);
        $this->assertStringContainsString('ALTER COLUMN "status" DROP DEFAULT', $result);
    }

    #[Test("PostgreSQL: formatDefault with boolean true outputs DEFAULT TRUE")]
    public function pgsql_default_boolean_true(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatColumn('active', ['type' => 'BOOLEAN', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'default' => true]);
        $this->assertStringContainsString('DEFAULT TRUE', $result);
    }

    #[Test("PostgreSQL: formatDefault with boolean false outputs DEFAULT FALSE")]
    public function pgsql_default_boolean_false(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatColumn('active', ['type' => 'BOOLEAN', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'default' => false]);
        $this->assertStringContainsString('DEFAULT FALSE', $result);
    }

    #[Test("PostgreSQL: COMMENT ON COLUMN is generated via formatRelationships")]
    public function pgsql_comment_on_column(): void
    {
        $f = new PostgreSqlFormatter();
        $f->formatColumn('name', ['type' => 'STRING', 'nullable' => false, 'primary' => false, 'unique' => false, 'autoIncrement' => false, 'comment' => 'user full name']);
        $sql = $f->formatRelationships('users');
        $this->assertStringContainsString("COMMENT ON COLUMN", $sql);
        $this->assertStringContainsString('"users"."name"', $sql);
        $this->assertStringContainsString("'user full name'", $sql);
        $this->assertStringContainsString("'user full name'", $sql);
    }

    #[Test("PostgreSQL: formatTableOptions returns empty string")]
    public function pgsql_table_options(): void
    {
        $f = new PostgreSqlFormatter();
        $this->assertSame('', $f->formatTableOptions());
    }

    #[Test("PostgreSQL: relationships generate correct ALTER TABLE SQL")]
    public function pgsql_relationships(): void
    {
        $f = new PostgreSqlFormatter();
        $f->addRelationship('belongsTo', ['foreignKey' => 'user_id', 'relatedTable' => 'users', 'onDelete' => 'CASCADE']);
        $sql = $f->formatRelationships('posts');
        $this->assertStringContainsString('ALTER TABLE "posts" ADD FOREIGN KEY ("user_id") REFERENCES "users"(id) ON DELETE CASCADE', $sql);
    }

    #[Test("PostgreSQL: skipForeignKeys skips relationship output")]
    public function pgsql_skip_foreign_keys(): void
    {
        $f = new PostgreSqlFormatter();
        $f->skipForeignKeys = true;
        $f->addRelationship('belongsTo', ['foreignKey' => 'user_id', 'relatedTable' => 'users', 'onDelete' => 'CASCADE']);
        $this->assertSame('', $f->formatRelationships('posts'));
    }

    #[Test("PostgreSQL: formatAlterColumn with multiple changes")]
    public function pgsql_alter_column_multiple(): void
    {
        $f = new PostgreSqlFormatter();
        $result = $f->formatAlterColumn('users', 'email', ['type' => 'STRING', 'nullable' => false, 'default' => 'none@example.com']);
        $this->assertStringContainsString('SET DATA TYPE', $result);
        $this->assertStringContainsString('SET NOT NULL', $result);
        $this->assertStringContainsString("DEFAULT 'none@example.com'", $result);
    }
}
