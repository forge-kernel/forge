<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Services;

use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;
use PDO;
use Throwable;

#[Service]
final class MigrationConflictDetector
{
    use OutputHelper;

    private ?array $tablesCache = null;

    public function __construct(
        private readonly DatabaseConnectionInterface $connection,
        private readonly MigrationMetadataResolver $metadataResolver
    ) {}

    /**
     * Detect tables that exist but aren't tracked in migrations
     */
    public function detectConflicts(array $pendingMigrations): array
    {
        if (empty($pendingMigrations)) {
            return [];
        }

        $existingTables = $this->getExistingTables();
        $migrationTables = $this->extractTableNamesFromMigrations($pendingMigrations);
        
        $conflicts = [];
        foreach ($migrationTables as $migrationPath => $tableName) {
            if ($tableName && in_array($tableName, $existingTables)) {
                $migrationName = basename($migrationPath);
                $conflicts[] = [
                    "migration" => $migrationName,
                    "path" => $migrationPath,
                    "table" => $tableName,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Detect conflicts for multiple migration sets
     */
    public function detectConflictsInBatches(array $migrationBatches): array
    {
        $allConflicts = [];
        
        foreach ($migrationBatches as $batch => $migrations) {
            $conflicts = $this->detectConflicts($migrations);
            if (!empty($conflicts)) {
                $allConflicts[$batch] = $conflicts;
            }
        }

        return $allConflicts;
    }

    /**
     * Check if specific tables exist
     */
    public function tablesExist(array $tableNames): array
    {
        if (empty($tableNames)) {
            return [];
        }

        $existingTables = $this->getExistingTables();
        $results = [];

        foreach ($tableNames as $tableName) {
            $results[$tableName] = in_array($tableName, $existingTables);
        }

        return $results;
    }

    /**
     * Get conflicts by table type (based on migration patterns)
     */
    public function getConflictsByType(array $pendingMigrations): array
    {
        $conflicts = $this->detectConflicts($pendingMigrations);
        $conflictsByType = [];

        foreach ($conflicts as $conflict) {
            $type = $this->metadataResolver->extractType($conflict['path']);
            $conflictsByType[$type][] = $conflict;
        }

        return $conflictsByType;
    }

    /**
     * Get conflicts by module
     */
    public function getConflictsByModule(array $pendingMigrations): array
    {
        $conflicts = $this->detectConflicts($pendingMigrations);
        $conflictsByModule = [];

        foreach ($conflicts as $conflict) {
            $module = $this->metadataResolver->extractModule($conflict['path']);
            $key = $module ?? 'app';
            $conflictsByModule[$key][] = $conflict;
        }

        return $conflictsByModule;
    }

    /**
     * Get statistics about conflicts
     */
    public function getConflictStatistics(array $pendingMigrations): array
    {
        $conflicts = $this->detectConflicts($pendingMigrations);
        $totalTables = $this->getExistingTablesCount();
        $migrationTables = array_filter($this->extractTableNamesFromMigrations($pendingMigrations));

        return [
            'total_pending_migrations' => count($pendingMigrations),
            'total_existing_tables' => $totalTables,
            'migration_tables_count' => count($migrationTables),
            'conflicts_count' => count($conflicts),
            'conflict_percentage' => count($pendingMigrations) > 0 
                ? round((count($conflicts) / count($pendingMigrations)) * 100, 2) 
                : 0,
            'conflicts_by_type' => $this->getConflictsByType($pendingMigrations),
            'conflicts_by_module' => $this->getConflictsByModule($pendingMigrations)
        ];
    }

    /**
     * Get existing tables with caching to avoid repeated queries
     */
    private function getExistingTables(): array
    {
        if ($this->tablesCache !== null) {
            return $this->tablesCache;
        }

        try {
            $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            $query = match ($driver) {
                'mysql' => 'SHOW TABLES',
                'pgsql' => "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema'",
                'sqlite' => "SELECT name FROM sqlite_master WHERE type='table'",
                default => throw new \RuntimeException("Unsupported database driver: $driver")
            };

            $stmt = $this->connection->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Handle different result formats for different drivers
            if ($driver === 'mysql') {
                $tables = array_map(fn($row) => reset($row), $results);
            } else {
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            $this->tablesCache = $tables;
            return $tables;
        } catch (Throwable $e) {
            $this->tablesCache = [];
            return [];
        }
    }

    /**
     * Get total count of existing tables
     */
    private function getExistingTablesCount(): int
    {
        return count($this->getExistingTables());
    }

    /**
     * Extract table names from multiple migration paths efficiently
     */
    private function extractTableNamesFromMigrations(array $migrationPaths): array
    {
        $tableNames = [];

        foreach ($migrationPaths as $path) {
            $tableName = $this->extractTableNameFromPath($path);
            if ($tableName) {
                $tableNames[$path] = $tableName;
            }
        }

        return $tableNames;
    }

    /**
     * Extract table name from migration path
     */
    private function extractTableNameFromPath(string $migrationPath): ?string
    {
        $filename = basename($migrationPath, ".php");

        // Handle different migration naming patterns
        $patterns = [
            '/Create(\w+)Table/',
            '/create_(\w+)_table/',
            '/(\w+)_migration/',
            '/(\w+)/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $filename, $matches)) {
                $tableName = $matches[1];
                return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $tableName));
            }
        }

        return null;
    }

    /**
     * Clear the tables cache
     */
    public function clearTablesCache(): void
    {
        $this->tablesCache = null;
    }

    /**
     * Check if a specific migration conflicts
     */
    public function hasConflict(string $migrationPath): bool
    {
        $tableName = $this->extractTableNameFromPath($migrationPath);
        
        if (!$tableName) {
            return false;
        }

        $existingTables = $this->getExistingTables();
        return in_array($tableName, $existingTables);
    }

    /**
     * Get potential conflicts for future migrations
     */
    public function getPotentialConflicts(array $futureMigrations): array
    {
        $existingTables = $this->getExistingTables();
        $potentialConflicts = [];

        foreach ($futureMigrations as $migrationPath => $tableName) {
            if (in_array($tableName, $existingTables)) {
                $potentialConflicts[] = [
                    'path' => $migrationPath,
                    'table' => $tableName,
                    'status' => 'existing_table'
                ];
            }
        }

        return $potentialConflicts;
    }
}
