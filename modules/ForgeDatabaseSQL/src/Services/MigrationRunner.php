<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Services;

use Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Modules\ForgeDatabaseSQL\DB\Migrations\Migration;
use Modules\ForgeDatabaseSQL\DB\Schema\MySqlFormatter;
use Modules\ForgeDatabaseSQL\DB\Schema\PostgreSqlFormatter;
use Modules\ForgeDatabaseSQL\DB\Schema\SqliteFormatter;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Container;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Helpers\ModuleHelper;
use Forge\Core\Structure\StructureResolver;
use Forge\Traits\StringHelper;
use PDO;
use ReflectionException;
use RuntimeException;
use ReflectionClass;
use Throwable;

/**
 * Service for executing individual migrations with optimized performance
 */
#[Service]
final class MigrationRunner
{
    public function __construct(
        private readonly DatabaseConnectionInterface $connection,
        private readonly MigrationMetadataResolver $metadataResolver
    ) {}

    /**
     * Execute a single migration
     */
    public function run(string $migrationPath, bool $verbose = false): bool
    {
        $migrationName = basename($migrationPath);
        [$type, $module, $group] = $this->metadataResolver->extractMetadata($migrationPath);

        $migration = $this->resolveMigration($migrationPath);
        
        try {
            $migration->up();
            
            if ($verbose) {
                echo "Migration completed: {$migrationName}\n";
            }
            
            return true;
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Migration failed: {$migrationName}. Error: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Mark migration as complete without running
     */
    public function markAsComplete(string $migrationPath, int $batch, bool $verbose = false): bool
    {
        $migrationName = basename($migrationPath);
        [$type, $module, $group] = $this->metadataResolver->extractMetadata($migrationPath);

        try {
            $stmt = $this->connection->prepare(
                "INSERT INTO forge_migrations (migration, batch, type, module, migration_group)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $migrationName,
                $batch,
                $type,
                $module,
                $group,
            ]);
            
            if ($verbose) {
                echo "Marked as complete: {$migrationName}\n";
            }
            
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Rollback a migration
     */
    public function rollback(string $migrationPath): bool
    {
        $migrationName = basename($migrationPath);
        
        try {
            $migration = $this->resolveMigration($migrationPath);
            $migration->down();
            
            $stmt = $this->connection->prepare(
                "DELETE FROM forge_migrations WHERE migration = ?"
            );
            
            $stmt->execute([$migrationName]);
            
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function resolveMigration(string $path): object|string
    {
        require_once $path;
        $className = $this->getMigrationClassName($path);
        $reflection = new ReflectionClass($className);

        if (!$reflection->isSubclassOf(Migration::class)) {
            throw new RuntimeException("Invalid migration class: $className");
        }

        $driver = $this->connection
            ->getPdo()
            ->getAttribute(PDO::ATTR_DRIVER_NAME);

        $formatter = match ($driver) {
            "mysql" => new MySqlFormatter(),
            "sqlite" => new SqliteFormatter(),
            "pgsql" => new PostgreSqlFormatter(),
            default => throw new RuntimeException(
                "Unsupported Database driver: $driver",
            ),
        };

        return $reflection->newInstance($this->connection, $formatter);
    }

    private function getMigrationClassName(string $path): string
    {
        return preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', basename($path, '.php'));
    }
}