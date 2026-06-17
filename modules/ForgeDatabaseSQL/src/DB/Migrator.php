<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB;

use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;
use App\Modules\ForgeDatabaseSQL\DB\Schema\MySqlFormatter;
use App\Modules\ForgeDatabaseSQL\DB\Schema\PostgreSqlFormatter;
use App\Modules\ForgeDatabaseSQL\DB\Schema\SqliteFormatter;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Attributes\Migration as MigrationAttribute;
use Forge\Core\DI\Container;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Helpers\ModuleHelper;
use Forge\Core\Services\AttributeDiscoveryService;
use Forge\Core\Structure\StructureResolver;
use Forge\Traits\StringHelper;
use Forge\CLI\Traits\OutputHelper;
use PDO;
use ReflectionException;
use RuntimeException;
use ReflectionClass;
use Throwable;

final class Migrator
{
    use StringHelper;
    use OutputHelper;

    private const string MIGRATIONS_TABLE = "forge_migrations";
    private const string CORE_MIGRATIONS_PATH =
        BASE_PATH . "/kernel/Database/Migrations";
    private const string MODULES_PATH = BASE_PATH . "/modules";
    private ?int $currentBatch = null;
    private ?StructureResolver $structureResolver = null;
    private ?Container $container = null;

    public function __construct(
        private DatabaseConnectionInterface $connection,
        ?Container $container = null,
    ) {
        $this->container = $container;
        $this->ensureMigrationsTable();
        if ($container && $container->has(StructureResolver::class)) {
            $this->structureResolver = $container->get(
                StructureResolver::class,
            );
        }
    }

    /**
     * Ensures the migration table exists with necessary metadata columns.
     */
    private function ensureMigrationsTable(): void
    {
        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS " .
            self::MIGRATIONS_TABLE .
            " (
                migration VARCHAR(255) PRIMARY KEY,
                batch INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                module VARCHAR(255) NULL,
                migration_group VARCHAR(255) NULL
            )",
        );
    }

    public function createMigrationTable(): void
    {
        $this->ensureMigrationsTable();
    }

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Retrieves the list of migrations that are pending to be run based on filters (for preview).
     *
     * @param string|null $scope Defaults to 'all'. The type of migrations to preview: 'all', 'app', 'core', or 'module'.
     * @throws ReflectionException
     */
    public function previewRun(
        ?string $scope = "all",
        ?string $module = null,
        ?string $group = null,
    ): array {
        return $this->getPendingMigrations($scope, $module, $group);
    }

    /**
     * Discovers pending migrations based on the given scope and filters.
     *
     * @param string|null $scope
     * @param string|null $module
     * @param string|null $group
     * @return array<string> List of full file paths.
     * @throws ReflectionException
     */
    private function getPendingMigrations(
        ?string $scope,
        ?string $module,
        ?string $group,
    ): array {
        $ran = $this->getRanMigrationNames();
        // Normalize ran migrations to lowercase for case-insensitive comparison
        $ranNormalized = array_map("strtolower", $ran);
        $ranLookup = array_flip($ranNormalized);

        $scope = $scope ?? "all";
        $module = $module ? $this->toPascalCase($module) : null;

        $moduleForDiscovery =
            $scope === "module" && $module !== null ? $module : null;

        $allFiles = $this->discoverMigrationFiles($scope, $moduleForDiscovery);

        $pendingFiles = [];

        foreach ($allFiles as $path) {
            $migrationName = basename($path);
            $migrationNameNormalized = strtolower($migrationName);

            if (isset($ranLookup[$migrationNameNormalized])) {
                continue;
            }

            [
                ,
                $migrationType,
                $migrationModule,
                $migrationGroup,
            ] = $this->extractMigrationMetadata($path);

            if ($module !== null) {
                if (
                    $migrationType !== "module" ||
                    $migrationModule !== $this->toPascalCase($module)
                ) {
                    continue;
                }
            }

            if ($group !== null && $migrationGroup !== $group) {
                continue;
            }

            $pendingFiles[] = $path;
        }

        return $pendingFiles;
    }

    private function getRanMigrationNames(): array
    {
        $stmt = $this->connection->query(
            "SELECT migration FROM " . self::MIGRATIONS_TABLE,
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @return array<string> List of full file paths, sorted alphabetically/chronologically.
     */
    private function discoverMigrationFiles(
        string $scope,
        ?string $module,
    ): array {
        $files = [];

        $paths = [];
        switch ($scope) {
            case "core":
                $paths[] = self::CORE_MIGRATIONS_PATH;
                break;
            case "app":
                $appMigrationsPath = $this->getAppMigrationsPath();
                $paths[] = $appMigrationsPath;
                break;
            case "module":
                $paths = $this->getModuleMigrationPaths($module);
                break;
            case "all":
                $paths[] = self::CORE_MIGRATIONS_PATH;
                $appMigrationsPath = $this->getAppMigrationsPath();
                $paths[] = $appMigrationsPath;
                $paths = array_merge(
                    $paths,
                    $this->getModuleMigrationPaths(null),
                );
                break;
            default:
                break;
        }

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = array_merge($files, glob($path . "/*.php"));
            }
        }

        $attributeFiles = $this->discoverAttributeBasedMigrations(
            $scope,
            $module,
        );
        $files = array_merge($files, $attributeFiles);

        $files = array_unique($files);
        sort($files);

        return $files;
    }

    /**
     * Discover migrations using #[Migration] attribute
     *
     * @return array<string> List of full file paths
     */
    private function discoverAttributeBasedMigrations(
        string $scope,
        ?string $module,
    ): array {
        $discoveryService = new AttributeDiscoveryService();
        $basePaths = $this->getBasePathsForMigrationDiscovery($scope, $module);

        $classMap = $discoveryService->discover($basePaths, [
            MigrationAttribute::class,
        ]);

        $files = [];
        foreach ($classMap as $className => $metadata) {
            if (class_exists($className)) {
                try {
                    $reflection = new ReflectionClass($className);
                    if ($reflection->isSubclassOf(Migration::class)) {
                        $filepath = $metadata["file"] ?? "";
                        if (
                            $filepath &&
                            FileExistenceCache::exists($filepath)
                        ) {
                            if (
                                $this->matchesScopeAndModule(
                                    $filepath,
                                    $scope,
                                    $module,
                                )
                            ) {
                                $files[] = $filepath;
                            }
                        }
                    }
                } catch (ReflectionException $e) {
                }
            }
        }

        return $files;
    }

    /**
     * Get base paths for migration discovery based on scope
     *
     * @return array<string>
     */
    private function getBasePathsForMigrationDiscovery(
        string $scope,
        ?string $module,
    ): array {
        $basePaths = [];

        switch ($scope) {
            case "core":
                $basePaths[] = "kernel";
                break;
            case "app":
                $basePaths[] = "app";
                break;
            case "module":
                if ($module) {
                    $basePaths[] = "modules/$module/src";
                }
                break;
            case "all":
                $basePaths[] = "app";
                $basePaths[] = "kernel";
                if (is_dir(self::MODULES_PATH)) {
                    $modules = array_filter(
                        scandir(self::MODULES_PATH),
                        fn($item) => is_dir(self::MODULES_PATH . "/" . $item) &&
                        !in_array($item, [".", ".."]),
                    );
                    foreach ($modules as $moduleName) {
                        if (!ModuleHelper::isModuleDisabled($moduleName)) {
                            $basePaths[] = "modules/$moduleName/src";
                        }
                    }
                }
                break;
        }

        return $basePaths;
    }

    /**
     * Check if a migration file matches the requested scope and module
     */
    private function matchesScopeAndModule(
        string $filepath,
        string $scope,
        ?string $module,
    ): bool {
        $relativePath = str_replace(BASE_PATH . "/", "", $filepath);

        if ($scope === "core") {
            return str_starts_with($relativePath, "kernel/");
        }

        if ($scope === "app") {
            if ($this->structureResolver) {
                try {
                    $appMigrationsPath = $this->structureResolver->getAppPath(
                        "migrations",
                    );
                    return str_starts_with($relativePath, $appMigrationsPath);
                } catch (\InvalidArgumentException $e) {
                    return str_starts_with(
                        $relativePath,
                        "app/Database/Migrations",
                    );
                }
            }
            return str_starts_with($relativePath, "app/");
        }

        if ($scope === "module" && $module) {
            $modulePath = "modules/$module/";
            if (str_starts_with($relativePath, $modulePath)) {
                if ($this->structureResolver) {
                    try {
                        $moduleMigrationsPath = $this->structureResolver->getModulePath(
                            $module,
                            "migrations",
                        );
                        $expectedPath = "$modulePath$moduleMigrationsPath";
                        return str_starts_with($relativePath, $expectedPath);
                    } catch (\InvalidArgumentException $e) {
                        return str_starts_with(
                            $relativePath,
                            "$modulePath" . "src/Database/Migrations",
                        );
                    }
                }
                return true;
            }
            return false;
        }

        return true;
    }

    private function getAppMigrationsPath(): string
    {
        if ($this->structureResolver) {
            try {
                $appMigrationsPath = $this->structureResolver->getAppPath(
                    "migrations",
                );
                return BASE_PATH . "/" . $appMigrationsPath;
            } catch (\InvalidArgumentException $e) {
                return BASE_PATH . "/app/Database/Migrations";
            }
        }
        return BASE_PATH . "/app/Database/Migrations";
    }

    /**
     * Returns an array of directory paths for module migrations.
     * @param string|null $target Target module name, or null for all modules.
     * @return array<string> List of full directory paths
     */
    private function getModuleMigrationPaths(?string $target = null): array
    {
        $paths = [];
        if (!is_dir(self::MODULES_PATH)) {
            return [];
        }

        $modules = $target
            ? [$target]
            : array_filter(scandir(self::MODULES_PATH), function ($item) {
                return is_dir(self::MODULES_PATH . "/" . $item) &&
                    !in_array($item, [".", ".."]);
            });

        foreach ($modules as $moduleName) {
            if (ModuleHelper::isModuleDisabled($moduleName)) {
                continue;
            }

            if ($this->structureResolver) {
                try {
                    $moduleMigrationsPath = $this->structureResolver->getModulePath(
                        $moduleName,
                        "migrations",
                    );
                    $central =
                        self::MODULES_PATH .
                        "/" .
                        $moduleName .
                        "/" .
                        $moduleMigrationsPath;
                    if (is_dir($central)) {
                        $paths[] = $central;
                    }

                    $tenant = $central . "/Tenants";
                    if (is_dir($tenant)) {
                        $paths[] = $tenant;
                    }
                } catch (\InvalidArgumentException $e) {
                    $central =
                        self::MODULES_PATH .
                        "/" .
                        $moduleName .
                        "/src/Database/Migrations";
                    if (is_dir($central)) {
                        $paths[] = $central;
                    }

                    $tenant =
                        self::MODULES_PATH .
                        "/" .
                        $moduleName .
                        "/src/Database/Migrations/Tenants";
                    if (is_dir($tenant)) {
                        $paths[] = $tenant;
                    }
                }
            } else {
                $central =
                    self::MODULES_PATH .
                    "/" .
                    $moduleName .
                    "/src/Database/Migrations";
                if (is_dir($central)) {
                    $paths[] = $central;
                }

                $tenant =
                    self::MODULES_PATH .
                    "/" .
                    $moduleName .
                    "/src/Database/Migrations/Tenants";
                if (is_dir($tenant)) {
                    $paths[] = $tenant;
                }
            }
        }

        return $paths;
    }

    /**
     * Uses reflection and path analysis to determine migration metadata.
     *
     * @param string $path Full path to the migration file.
     * @return array{0: string, 1: string, 2: ?string, 3: ?string} [ClassName, Type, Module, Group]
     * @throws ReflectionException
     */
    private function extractMigrationMetadata(string $path): array
    {
        require_once $path;
        $className = $this->getMigrationClassName($path);
        $reflection = new ReflectionClass($className);

        $group = null;
        $type = "app";
        $module = null;

        $attributes = $reflection->getAttributes(GroupMigration::class);
        if (!empty($attributes)) {
            $instance = $attributes[0]->newInstance();
            $group = $instance->name ?? null;
        }

        $relativePath = str_replace(BASE_PATH . "/", "", $path);

        if (str_starts_with($relativePath, "kernel/Database/Migrations")) {
            $type = "core";
        } elseif (str_starts_with($relativePath, "modules/")) {
            $type = "module";
            if (preg_match("/^modules\/([^\/]+)\//", $relativePath, $matches)) {
                $module = $matches[1];
            }
        } else {
            if ($this->structureResolver) {
                try {
                    $appMigrationsPath = $this->structureResolver->getAppPath(
                        "migrations",
                    );
                    if (str_starts_with($relativePath, $appMigrationsPath)) {
                        $type = "app";
                    } else {
                        $type = "app";
                    }
                } catch (\InvalidArgumentException $e) {
                    if (
                        str_starts_with(
                            $relativePath,
                            "app/Database/Migrations",
                        )
                    ) {
                        $type = "app";
                    }
                }
            } else {
                if (str_starts_with($relativePath, "app/Database/Migrations")) {
                    $type = "app";
                }
            }
        }

        return [$className, $type, $module, $group];
    }

    private function getMigrationClassName(string $path): string
    {
        $filename = basename($path, ".php");
        return preg_replace("/^\d{4}_\d{2}_\d{2}_\d{6}_/", "", $filename);
    }

    public function previewRollback(int $steps = 1): array
    {
        return $this->getRanMigrations($steps);
    }

    /**
     * Retrieves ran migrations based on complex filters for rollback.
     *
     * @param int $steps
     * @param string|null $type
     * @param string|null $module
     * @param string|null $group
     * @param int|null $batch
     * @return array<string> List of migration filenames.
     */
    public function getRanMigrations(
        int $steps,
        ?string $type = null,
        ?string $module = null,
        ?string $group = null,
        ?int $batch = null,
    ): array {
        $module = $module ? $this->toPascalCase($module) : null;

        $sql = "SELECT migration FROM " . self::MIGRATIONS_TABLE . " WHERE 1=1";
        $params = [];

        if ($batch === null) {
            $lastBatch = $this->getLastBatch();
            $minBatch = $lastBatch - $steps + 1;

            $sql .= " AND batch >= ?";
            $params[] = $minBatch;
        } else {
            $sql .= " AND batch = ?";
            $params[] = $batch;
        }

        if ($type !== null && strtolower($type) !== "all") {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        if ($module !== null) {
            $sql .= " AND module = ?";
            $params[] = $module;
        }

        if ($group !== null) {
            $sql .= " AND migration_group = ?";
            $params[] = $group;
        }

        $sql .= " ORDER BY batch DESC, migration DESC";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getLastBatch(): int
    {
        $stmt = $this->connection->query(
            "SELECT MAX(batch) FROM " . self::MIGRATIONS_TABLE,
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Runs pending migrations based on scope and group filters.
     *
     * @param string|null $scope Defaults to 'all'. The type of migrations to run: 'all', 'app', 'core', or 'module'.
     * @param string|null $module The specific module name if scope is 'module'.
     * @param string|null $group The group name to filter migrations by.
     * @throws ReflectionException
     * @throws Throwable
     */
    public function run(
        ?string $scope = "all",
        ?string $module = null,
        ?string $group = null,
        bool $forceSkip = false,
    ): void {
        $pendingMigrations = $this->getPendingMigrations(
            $scope,
            $module,
            $group,
        );

        if (empty($pendingMigrations)) {
            $this->info(
                "No migrations are currently PENDING matching the specified criteria.",
            );
            return;
        }

        // Check for existing tables not tracked in migrations
        $untrackedTables = $this->detectUntrackedTables($pendingMigrations);
        if (!empty($untrackedTables) && !$forceSkip) {
            $this->handleUntrackedTables(
                $untrackedTables,
                $pendingMigrations,
                $scope,
                $module,
                $group,
            );
            return;
        }

        $this->currentBatch = $this->getNextBatchNumber();
        $this->connection->beginTransaction();
        try {
            foreach ($pendingMigrations as $migrationPath) {
                $this->runMigration($migrationPath, true);
            }
            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        } finally {
            $this->currentBatch = null;
        }
    }

    private function getNextBatchNumber(): int
    {
        $stmt = $this->connection->query(
            "SELECT MAX(batch) FROM " . self::MIGRATIONS_TABLE,
        );
        return (int) $stmt->fetchColumn() + 1;
    }

    /**
     * Runs the migration and records its metadata in the database.
     * @throws ReflectionException
     */
    private function runMigration(string $path): void
    {
        if ($this->currentBatch === null) {
            throw new RuntimeException("Migration batch number not set.");
        }

        $migrationName = basename($path);

        $stmt = $this->connection->prepare(
            "SELECT COUNT(*) FROM " .
            self::MIGRATIONS_TABLE .
            " WHERE migration = ?",
        );
        $stmt->execute([$migrationName]);
        $exists = (int) $stmt->fetchColumn() > 0;

        if ($exists) {
            return;
        }

        [, $type, $module, $group] = $this->extractMigrationMetadata($path);

        $migration = $this->resolveMigration($path);

        try {
            $migration->up();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Migration failed: {$migrationName}. Error: " .
                $e->getMessage(),
                0,
                $e,
            );
        }

        $stmt = $this->connection->prepare(
            "INSERT INTO " .
            self::MIGRATIONS_TABLE .
            " (migration, batch, type, module, migration_group)
            VALUES (?, ?, ?, ?, ?)",
        );

        $stmt->execute([
            $migrationName,
            $this->currentBatch,
            $type,
            $module,
            $group,
        ]);
    }

    /**
     * @throws ReflectionException
     */
    private function resolveMigration(string $path): object|string
    {
        require_once $path;
        $className = $this->getMigrationClassName($path);
        $reflection = new ReflectionClass($className);

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

        if (!$reflection->isSubclassOf(Migration::class)) {
            throw new RuntimeException("Invalid migration class: $className");
        }

        return $reflection->newInstance($this->connection, $formatter);
    }

    private function getPdo(): PDO
    {
        return $this->connection->getPdo();
    }

    /**
     * Rollback migrations based on complex filters.
     *
     * @param int $steps Number of batches to roll back (default 1). Ignored if $batch is set.
     * @param string|null $type Filter by type ('all', 'app', 'core', 'module').
     * @param string|null $module Filter by specific module name.
     * @param string|null $group Filter by migration group name.
     * @param int|null $batch Filter by specific batch number.
     * @throws Throwable
     */
    public function rollback(
        int $steps = 1,
        ?string $type = null,
        ?string $module = null,
        ?string $group = null,
        ?int $batch = null,
    ): void {
        $this->connection->beginTransaction();
        try {
            $migrations = $this->getRanMigrations(
                $steps,
                $type,
                $module,
                $group,
                $batch,
            );

            foreach ($migrations as $migration) {
                $this->rollbackMigration($migration);
            }

            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Detect tables that exist but aren't tracked in migrations
     */
    private function detectUntrackedTables(array $pendingMigrations): array
    {
        $existingTables = $this->getExistingTables();
        $untrackedTables = [];

        foreach ($pendingMigrations as $migrationPath) {
            require_once $migrationPath;
            $className = $this->getMigrationClassName($migrationPath);

            if (!class_exists($className)) {
                continue;
            }

            // Try to extract table name from migration
            $tableName = $this->extractTableNameFromMigrationClass(
                $className,
                $migrationPath,
            );

            if ($tableName && in_array($tableName, $existingTables)) {
                $migrationName = basename($migrationPath);
                $untrackedTables[] = [
                    "migration" => $migrationName,
                    "path" => $migrationPath,
                    "table" => $tableName,
                ];

                // Debug output
                $this->info(
                    "Conflict detected: Table '{$tableName}' exists for migration '{$migrationName}'",
                );
            }
        }

        if (!empty($untrackedTables)) {
            $this->info(
                "Found " .
                count($untrackedTables) .
                " untracked tables requiring resolution",
            );
        }

        return $untrackedTables;
    }

    /**
     * Extract table name from migration class
     */
    private function extractTableNameFromMigrationClass(
        string $className,
        string $migrationPath,
    ): ?string {
        // Try to get table name from filename first
        $filename = basename($migrationPath, ".php");

        if (preg_match("/Create(\w+)Table/", $filename, $matches)) {
            return strtolower(
                preg_replace("/([a-z])([A-Z])/", '$1_$2', $matches[1]),
            );
        }

        // Try reflection for other methods
        try {
            $reflection = new ReflectionClass($className);
            if (method_exists($reflection->getName(), "getTableName")) {
                $instance = $reflection->newInstanceWithoutConstructor();
                return $instance->getTableName();
            }
        } catch (\Throwable $e) {
            // Ignore reflection errors
        }

        return null;
    }

    /**
     * Get list of existing tables in database
     */
    private function getExistingTables(): array
    {
        try {
            $stmt = $this->connection->query(
                "SELECT name FROM sqlite_master WHERE type='table'",
            );
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Handle untracked tables with interactive options
     */
    private function handleUntrackedTables(
        array $untrackedTables,
        array $pendingMigrations,
        ?string $scope,
        ?string $module,
        ?string $group,
    ): void {
        $messages = [];
        foreach ($untrackedTables as $untracked) {
            $messages[] = "Table '{$untracked["table"]}' exists but migration '{$untracked["migration"]}' is not recorded";
        }

        $this->showWarningBox("Migration Conflict Detected", $messages);

        // Get TemplateGenerator from container (lazy initialization)
        $templateGenerator = null;
        if (
            $this->container &&
            $this->container->has(\Forge\Core\Services\TemplateGenerator::class)
        ) {
            $templateGenerator = $this->container->get(
                \Forge\Core\Services\TemplateGenerator::class,
            );
        }

        if (!$templateGenerator) {
            // Fallback to basic text input if TemplateGenerator not available
            $this->handleUntrackedTablesFallback(
                $untrackedTables,
                $pendingMigrations,
                $scope,
                $module,
                $group,
            );
            return;
        }

        $choice = $templateGenerator->selectFromList(
            "How would you like to proceed?",
            [
                "Drop tables and re-run migrations (DESTRUCTIVE - will delete all data)",
                "Mark migrations as complete (SAFE - recommended)",
                "Skip migrations",
            ],
            "Mark migrations as complete (SAFE - recommended)",
        );

        if ($choice === null) {
            echo "Migration handling cancelled.\n";
            return;
        }

        if (str_contains($choice, "Drop tables")) {
            $this->dropUntrackedTablesAndRun(
                $untrackedTables,
                $pendingMigrations,
                $scope,
                $module,
                $group,
            );
        } elseif (str_contains($choice, "Mark migrations as complete")) {
            $this->markMigrationsAsComplete(
                $untrackedTables,
                $pendingMigrations,
            );
        } else {
            echo "Skipping migrations.\n";
        }
    }

    /**
     * Fallback handler when TemplateGenerator is not available
     */
    private function handleUntrackedTablesFallback(
        array $untrackedTables,
        array $pendingMigrations,
        ?string $scope,
        ?string $module,
        ?string $group,
    ): void {
        echo "\n\033[33mOptions:\033[0m\n";
        echo "  1. \033[31m[DESTRUCTIVE]\033[0m Drop existing tables and re-run migrations (WILL DELETE DATA)\n";
        echo "  2. \033[32m[SAFE]\033[0m Just mark migrations as complete (recommended)\n";
        echo "  3. Skip migrations\n";

        echo "\nPlease choose an option (1-3): ";
        $handle = fopen("php://stdin", "r");
        $choice = trim(fgets($handle));
        fclose($handle);

        switch ($choice) {
            case "1":
                $this->dropUntrackedTablesAndRun(
                    $untrackedTables,
                    $pendingMigrations,
                    $scope,
                    $module,
                    $group,
                );
                break;
            case "2":
                $this->markMigrationsAsComplete(
                    $untrackedTables,
                    $pendingMigrations,
                );
                break;
            case "3":
                echo "Skipping migrations.\n";
                break;
            default:
                echo "Invalid choice. Skipping migrations.\n";
        }
    }

    /**
     * Drop untracked tables and run migrations
     */
    private function dropUntrackedTablesAndRun(
        array $untrackedTables,
        array $pendingMigrations,
        ?string $scope,
        ?string $module,
        ?string $group,
    ): void {
        echo "\n\033[31mDROPPING TABLES - This will delete all data!\033[0m\n";

        foreach ($untrackedTables as $untracked) {
            echo "Dropping table: {$untracked["table"]}\n";
            $this->connection->exec(
                "DROP TABLE IF EXISTS {$untracked["table"]}",
            );
        }

        echo "Running migrations...\n";
        $this->run($scope, $module, $group, true);
    }

    /**
     * Mark migrations as complete without running them
     */
    private function markMigrationsAsComplete(
        array $untrackedTables,
        array $pendingMigrations,
    ): void {
        echo "\n\033[32mMarking migrations as complete...\033[0m\n";

        $this->currentBatch = $this->getNextBatchNumber();
        $this->connection->beginTransaction();

        try {
            foreach ($pendingMigrations as $migrationPath) {
                $migrationName = basename($migrationPath);
                [, $type, $module, $group] = $this->extractMigrationMetadata(
                    $migrationPath,
                );

                echo "Marking migration as complete: {$migrationName}\n";

                $stmt = $this->connection->prepare(
                    "INSERT INTO " .
                    self::MIGRATIONS_TABLE .
                    " (migration, batch, type, module, migration_group)
                VALUES (?, ?, ?, ?, ?)",
                );

                $stmt->execute([
                    $migrationName,
                    $this->currentBatch,
                    $type,
                    $module,
                    $group,
                ]);
            }

            $this->connection->commit();
            echo "\n\033[32m✅ All migrations marked as complete successfully!\033[0m\n";
        } catch (Throwable $e) {
            $this->connection->rollBack();
            echo "\n\033[31m❌ Failed to mark migrations as complete: " .
                $e->getMessage() .
                "\033[0m\n";
        }
    }

    private function findMigrationPath(string $filename): ?string
    {
        $paths = $this->discoverMigrationFiles("all", null);

        foreach ($paths as $path) {
            if (basename($path) === $filename) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Rollback a single migration
     */
    private function rollbackMigration(string $migration): void
    {
        $path = $this->findMigrationPath($migration);
        if (!$path) {
            throw new RuntimeException("Migration file not found: $migration");
        }

        require_once $path;
        $className = $this->getMigrationClassName($path);
        $reflection = new ReflectionClass($className);

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

        $instance = $reflection->newInstance($this->connection, $formatter);
        $instance->down();

        $stmt = $this->connection->prepare(
            "DELETE FROM " . self::MIGRATIONS_TABLE . " WHERE migration = ?",
        );

        $stmt->execute([basename($migration)]);
    }
}
