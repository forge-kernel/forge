<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Services;

use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Attributes\Service;
use PDO;
use Throwable;

#[Service]
final class MigrationTableManagerService
{
    private const string MIGRATIONS_TABLE = "forge_migrations";

    public function __construct(
        private readonly DatabaseConnectionInterface $connection
    ) {}

    /**
     * Ensure the migrations table exists with proper schema
     */
    public function ensureMigrationsTable(): bool
    {
        try {
            if ($this->migrationsTableExists()) {
                return $this->validateTableSchema();
            }

            return $this->createMigrationsTable();
        } catch (Throwable $e) {
            throw new \RuntimeException("Failed to ensure migrations table: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if migrations table exists
     */
    public function migrationsTableExists(): bool
    {
        try {
            $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            $query = match ($driver) {
                'mysql' => "SHOW TABLES LIKE '" . self::MIGRATIONS_TABLE . "'",
                'pgsql' => "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '" . self::MIGRATIONS_TABLE . "')",
                'sqlite' => "SELECT name FROM sqlite_master WHERE type='table' AND name='" . self::MIGRATIONS_TABLE . "'",
                default => throw new \RuntimeException("Unsupported database driver: $driver")
            };

            $stmt = $this->connection->query($query);
            $result = $stmt->fetchColumn();

            return (bool) $result;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Create migrations table with appropriate schema for the database driver
     */
    public function createMigrationsTable(): bool
    {
        try {
            $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            $createTableSQL = match ($driver) {
                'mysql' => $this->getMySQLCreateTableSQL(),
                'pgsql' => $this->getPostgreSQLCreateTableSQL(),
                'sqlite' => $this->getSQLiteCreateTableSQL(),
                default => throw new \RuntimeException("Unsupported database driver: $driver")
            };

            $this->connection->exec($createTableSQL);
            
            return $this->migrationsTableExists();
        } catch (Throwable $e) {
            throw new \RuntimeException("Failed to create migrations table: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate that the table has the required columns
     */
    public function validateTableSchema(): bool
    {
        try {
            $requiredColumns = ['migration', 'batch', 'type', 'module', 'migration_group'];
            $existingColumns = $this->getTableColumns();

            foreach ($requiredColumns as $column) {
                if (!in_array($column, $existingColumns)) {
                    return false;
                }
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get columns in the migrations table
     */
    public function getTableColumns(): array
    {
        try {
            $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            $query = match ($driver) {
                'mysql' => "SHOW COLUMNS FROM " . self::MIGRATIONS_TABLE,
                'pgsql' => "SELECT column_name FROM information_schema.columns WHERE table_name = '" . self::MIGRATIONS_TABLE . "'",
                'sqlite' => "PRAGMA table_info(" . self::MIGRATIONS_TABLE . ")",
                default => throw new \RuntimeException("Unsupported database driver: $driver")
            };

            $stmt = $this->connection->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($driver === 'mysql') {
                return array_column($results, 'Field');
            } elseif ($driver === 'pgsql') {
                return array_column($results, 'column_name');
            } elseif ($driver === 'sqlite') {
                return array_column($results, 'name');
            }

            return [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Add missing columns to existing table
     */
    public function addMissingColumns(): bool
    {
        try {
            $requiredColumns = [
                'migration' => 'VARCHAR(255) PRIMARY KEY',
                'batch' => 'INT NOT NULL',
                'type' => 'VARCHAR(50) NOT NULL',
                'module' => 'VARCHAR(255) NULL',
                'migration_group' => 'VARCHAR(255) NULL'
            ];

            $existingColumns = $this->getTableColumns();
            $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

            foreach ($requiredColumns as $column => $definition) {
                if (!in_array($column, $existingColumns)) {
                    $alterSQL = $this->getAlterTableSQL($column, $definition, $driver);
                    $this->connection->exec($alterSQL);
                }
            }

            return $this->validateTableSchema();
        } catch (Throwable $e) {
            throw new \RuntimeException("Failed to add missing columns: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Drop migrations table
     */
    public function dropMigrationsTable(): bool
    {
        try {
            $this->connection->exec("DROP TABLE IF EXISTS " . self::MIGRATIONS_TABLE);
            return !$this->migrationsTableExists();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Reset migrations table (truncate all records)
     */
    public function resetMigrationsTable(): bool
    {
        try {
            $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            $truncateSQL = match ($driver) {
                'mysql' => "TRUNCATE TABLE " . self::MIGRATIONS_TABLE,
                'pgsql' => "TRUNCATE TABLE " . self::MIGRATIONS_TABLE . " RESTART IDENTITY",
                'sqlite' => "DELETE FROM " . self::MIGRATIONS_TABLE,
                default => "DELETE FROM " . self::MIGRATIONS_TABLE
            };

            $this->connection->exec($truncateSQL);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get MySQL CREATE TABLE SQL
     */
    private function getMySQLCreateTableSQL(): string
    {
        return "
            CREATE TABLE " . self::MIGRATIONS_TABLE . " (
                migration VARCHAR(255) PRIMARY KEY,
                batch INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                module VARCHAR(255) NULL,
                migration_group VARCHAR(255) NULL,
                INDEX idx_batch (batch),
                INDEX idx_type (type),
                INDEX idx_module (module),
                INDEX idx_migration_group (migration_group)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
    }

    /**
     * Get PostgreSQL CREATE TABLE SQL
     */
    private function getPostgreSQLCreateTableSQL(): string
    {
        return "
            CREATE TABLE " . self::MIGRATIONS_TABLE . " (
                migration VARCHAR(255) PRIMARY KEY,
                batch INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                module VARCHAR(255) NULL,
                migration_group VARCHAR(255) NULL
            );
            
            CREATE INDEX idx_" . self::MIGRATIONS_TABLE . "_batch ON " . self::MIGRATIONS_TABLE . "(batch);
            CREATE INDEX idx_" . self::MIGRATIONS_TABLE . "_type ON " . self::MIGRATIONS_TABLE . "(type);
            CREATE INDEX idx_" . self::MIGRATIONS_TABLE . "_module ON " . self::MIGRATIONS_TABLE ."(module);
            CREATE INDEX idx_" . self::MIGRATIONS_TABLE . "_migration_group ON " . self::MIGRATIONS_TABLE ."(migration_group);
        ";
    }

    /**
     * Get SQLite CREATE TABLE SQL
     */
    private function getSQLiteCreateTableSQL(): string
    {
        return "
            CREATE TABLE " . self::MIGRATIONS_TABLE . " (
                migration VARCHAR(255) PRIMARY KEY,
                batch INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                module VARCHAR(255) NULL,
                migration_group VARCHAR(255) NULL
            );
            
            CREATE INDEX idx_" . self::MIGRATIONS_TABLE . "_batch ON " . self::MIGRATIONS_TABLE ."(batch);
            CREATE INDEX idx_" . self::MIGRATIONS_TABLE . "_type ON " . self::MIGRATIONS_TABLE ."(type);
            CREATE INDEX idx_" . self::MIGRATIONS_TABLE . "_module ON " . self::MIGRATIONS_TABLE ."(module);
            CREATE INDEX idx_" . self::MIGRATIONS_TABLE . "_migration_group ON " . self::MIGRATIONS_TABLE ."(migration_group);
        ";
    }

    /**
     * Get ALTER TABLE SQL for adding columns
     */
    private function getAlterTableSQL(string $column, string $definition, string $driver): string
    {
        return match ($driver) {
            'mysql' => "ALTER TABLE " . self::MIGRATIONS_TABLE . " ADD COLUMN $column $definition",
            'pgsql' => "ALTER TABLE " . self::MIGRATIONS_TABLE . " ADD COLUMN $column $definition",
            'sqlite' => throw new \RuntimeException("SQLite does not support ALTER TABLE ADD COLUMN in this context"),
            default => "ALTER TABLE " . self::MIGRATIONS_TABLE . " ADD COLUMN $column $definition"
        };
    }

    /**
     * Get table statistics
     */
    public function getTableStatistics(): array
    {
        try {
            $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            $countSQL = "SELECT COUNT(*) FROM " . self::MIGRATIONS_TABLE;
            $countStmt = $this->connection->query($countSQL);
            $totalMigrations = (int) $countStmt->fetchColumn();

            $batchSQL = "SELECT MAX(batch), MIN(batch) FROM " . self::MIGRATIONS_TABLE;
            $batchStmt = $this->connection->query($batchSQL);
            $batchData = $batchStmt->fetch(PDO::FETCH_ASSOC);

            $typeStatsSQL = "SELECT type, COUNT(*) as count FROM " . self::MIGRATIONS_TABLE . " GROUP BY type";
            $typeStatsStmt = $this->connection->query($typeStatsSQL);
            $typeStats = $typeStatsStmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total_migrations' => $totalMigrations,
                'last_batch' => (int) ($batchData['MAX(batch)'] ?? 0),
                'first_batch' => (int) ($batchData['MIN(batch)'] ?? 0),
                'type_distribution' => $typeStats,
                'table_exists' => $this->migrationsTableExists(),
                'schema_valid' => $this->validateTableSchema()
            ];
        } catch (Throwable $e) {
            return [
                'total_migrations' => 0,
                'last_batch' => 0,
                'first_batch' => 0,
                'type_distribution' => [],
                'table_exists' => false,
                'schema_valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}