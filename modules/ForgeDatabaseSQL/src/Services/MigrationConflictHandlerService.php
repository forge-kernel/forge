<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Services;

use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\CLI\Traits\OutputHelper;
use PDO;
use Throwable;

#[Service]
final class MigrationConflictHandlerService
{
    use OutputHelper;

    public function __construct(
        private readonly DatabaseConnectionInterface $connection,
        private readonly MigrationPathResolverService $pathResolver,
        private readonly ?Container $container = null
    ) {}

    /**
     * Detect conflicts between existing tables and pending migrations
     */
    public function detectConflicts(array $pendingMigrations): array
    {
        $existingTables = $this->getExistingTables();
        $conflicts = [];

        foreach ($pendingMigrations as $migrationPath) {
            $tableName = $this->extractTableNameFromMigrationPath($migrationPath);

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
     * Handle migration conflicts with user interaction
     */
    public function handleConflicts(
        array $conflicts,
        array $pendingMigrations,
        ?string $scope = null,
        ?string $module = null,
        ?string $group = null
    ): ?string {
        if (empty($conflicts)) {
            return null;
        }

        $this->displayConflictWarning($conflicts);

        $templateGenerator = $this->getTemplateGenerator();
        
        if ($templateGenerator) {
            return $this->handleWithTemplateGenerator(
                $conflicts,
                $pendingMigrations,
                $scope,
                $module,
                $group,
                $templateGenerator
            );
        }

        return $this->handleWithFallback(
            $conflicts,
            $pendingMigrations,
            $scope,
            $module,
            $group
        );
    }

    /**
     * Drop tables and run migrations
     */
    public function dropTablesAndRun(
        array $conflicts,
        array $pendingMigrations,
        callable $runMigrations
    ): void {
        $this->showWarning("DROPPING TABLES - This will delete all data!");

        foreach ($conflicts as $conflict) {
            echo "Dropping table: {$conflict["table"]}\n";
            $this->connection->exec("DROP TABLE IF EXISTS {$conflict["table"]}");
        }

        echo "Running migrations...\n";
        $runMigrations();
    }

    /**
     * Mark migrations as complete without running them
     */
    public function markMigrationsAsComplete(
        array $pendingMigrations,
        MigrationRepositoryService $repository,
        int $batch
    ): bool {
        $this->showInfo("Marking migrations as complete...");

        try {
            $migrationData = [];
            foreach ($pendingMigrations as $migrationPath) {
                $migrationName = basename($migrationPath);
                $type = $this->pathResolver->extractTypeFromPath($migrationPath);
                $module = $this->pathResolver->extractModuleFromPath($migrationPath);

                $migrationData[] = [
                    'migration' => $migrationName,
                    'batch' => $batch,
                    'type' => $type,
                    'module' => $module,
                    'group' => $this->extractGroupFromPath($migrationPath)
                ];

                echo "Marking migration as complete: {$migrationName}\n";
            }

            $success = $repository->recordMigrations($migrationData);

            if ($success) {
                $this->showSuccess("All migrations marked as complete successfully!");
            }

            return $success;
        } catch (Throwable $e) {
            $this->showError("Failed to mark migrations as complete: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get existing tables from database
     */
    private function getExistingTables(): array
    {
        try {
            $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            $query = match ($driver) {
                'mysql' => "SHOW TABLES",
                'pgsql' => "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema'",
                'sqlite' => "SELECT name FROM sqlite_master WHERE type='table'",
                default => throw new \RuntimeException("Unsupported database driver: $driver")
            };

            $stmt = $this->connection->query($query);
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // For MySQL, the column name is not predictable, so we need to get the first column
            if ($driver === 'mysql') {
                $tables = array_map(fn($row) => reset($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
            }

            return $tables;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Extract table name from migration path
     */
    private function extractTableNameFromMigrationPath(string $migrationPath): ?string
    {
        $filename = basename($migrationPath, ".php");

        if (preg_match("/Create(\w+)Table/", $filename, $matches)) {
            return strtolower(
                preg_replace("/([a-z])([A-Z])/", '$1_$2', $matches[1])
            );
        }

        return null;
    }

    /**
     * Extract group from migration path
     */
    private function extractGroupFromPath(string $path): ?string
    {
        try {
            require_once $path;
            $className = $this->pathResolver->getMigrationClassName($path);
            
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                $attributes = $reflection->getAttributes(\App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration::class);
                
                if (!empty($attributes)) {
                    $instance = $attributes[0]->newInstance();
                    return $instance->name ?? null;
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Display conflict warning
     */
    private function displayConflictWarning(array $conflicts): void
    {
        $messages = [];
        foreach ($conflicts as $conflict) {
            $messages[] = "Table '{$conflict["table"]}' exists but migration '{$conflict["migration"]}' is not recorded";
        }

        $this->showWarningBox("Migration Conflict Detected", $messages);
        $this->showInfo("Found " . count($conflicts) . " untracked tables requiring resolution");
    }

    /**
     * Get TemplateGenerator service if available
     */
    private function getTemplateGenerator(): ?object
    {
        if (
            $this->container &&
            $this->container->has(\Forge\Core\Services\TemplateGenerator::class)
        ) {
            return $this->container->get(\Forge\Core\Services\TemplateGenerator::class);
        }

        return null;
    }

    /**
     * Handle conflicts using TemplateGenerator
     */
    private function handleWithTemplateGenerator(
        array $conflicts,
        array $pendingMigrations,
        ?string $scope,
        ?string $module,
        ?string $group,
        object $templateGenerator
    ): string {
        $choice = $templateGenerator->selectFromList(
            "How would you like to proceed?",
            [
                "Drop tables and re-run migrations (DESTRUCTIVE - will delete all data)",
                "Mark migrations as complete (SAFE - recommended)",
                "Skip migrations",
            ],
            "Mark migrations as complete (SAFE - recommended)"
        );

        if ($choice === null) {
            return "cancelled";
        }

        if (str_contains($choice, "Drop tables")) {
            return "drop";
        } elseif (str_contains($choice, "Mark migrations as complete")) {
            return "mark";
        } else {
            return "skip";
        }
    }

    /**
     * Handle conflicts with fallback text input
     */
    private function handleWithFallback(
        array $conflicts,
        array $pendingMigrations,
        ?string $scope,
        ?string $module,
        ?string $group
    ): string {
        echo "\n\033[33mOptions:\033[0m\n";
        echo "  1. \033[31m[DESTRUCTIVE]\033[0m Drop existing tables and re-run migrations (WILL DELETE DATA)\n";
        echo "  2. \033[32m[SAFE]\033[0m Just mark migrations as complete (recommended)\n";
        echo "  3. Skip migrations\n";

        echo "\nPlease choose an option (1-3): ";
        $handle = fopen("php://stdin", "r");
        $choice = trim(fgets($handle));
        fclose($handle);

        return match ($choice) {
            "1" => "drop",
            "2" => "mark",
            "3" => "skip",
            default => "skip"
        };
    }

    /**
     * Show warning message
     */
    private function showWarning(string $message): void
    {
        echo "\n\033[31m$message\033[0m\n";
    }

    /**
     * Show info message
     */
    private function showInfo(string $message): void
    {
        echo "\n\033[32m$message\033[0m\n";
    }

    /**
     * Show success message
     */
    private function showSuccess(string $message): void
    {
        echo "\n\033[32m✅ $message\033[0m\n";
    }

    /**
     * Show error message
     */
    private function showError(string $message): void
    {
        echo "\n\033[31m❌ $message\033[0m\n";
    }
}