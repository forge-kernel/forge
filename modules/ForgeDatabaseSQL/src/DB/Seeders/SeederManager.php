<?php


declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\DB\Seeders;

use Modules\ForgeDatabaseSQL\DB\Seeders\Seeder;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Helpers\ModuleHelper;
use Forge\Core\Structure\StructureResolver;
use Forge\Traits\StringHelper;
use PDO;
use RuntimeException;
use Throwable;

final class SeederManager
{
    use StringHelper;

    private const string SEEDERS_TABLE = 'forge_seeders';

    public function __construct(
        private DatabaseConnectionInterface $connection,
        private readonly ?StructureResolver $structureResolver = null,
    ) {
        $this->ensureSeedersTable();
    }

    private function getModulesPath(): string
    {
        return BASE_PATH . '/' . ($this->structureResolver?->getModulesRoot() ?? StructureResolver::resolveModulesRoot());
    }

    private function ensureSeedersTable(): void
    {
        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS " . self::SEEDERS_TABLE . " (
                seeder VARCHAR(255) PRIMARY KEY,
                batch INT NOT NULL,
                ran_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        );
    }

    public function setConnection(DatabaseConnectionInterface $connection): void
    {
        $this->connection = $connection;
    }

    public function createSeedsTable(): void
    {
        $this->ensureSeedersTable();
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    public function run(?string $type = null, ?string $module = null): void
    {
        $this->connection->beginTransaction();
        try {
            $pending = $this->getPendingSeeders($type, $module);
            foreach ($pending as $seeder) {
                $this->runSeeder($seeder);
            }
            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function getPendingSeeders(?string $type, ?string $module): array
    {
        $ran = $this->getRanSeederNames();

        $paths = match ($type) {
            'module' => $this->getAllModuleSeederFiles($module),
            'app' => $this->getAllAppSeederFiles(),
            'tenants' => $this->getAllTenantSeederFiles(),
            'all' => array_merge(
                $this->getAllAppSeederFiles(),
                $this->getAllModuleSeederFiles()
            ),
            default => [],
        };

        return array_filter($paths, fn($path) => !in_array(basename($path), $ran));
    }

    private function getRanSeederNames(): array
    {
        $stmt = $this->connection->query("SELECT seeder FROM " . self::SEEDERS_TABLE);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getAppSeederFiles(): array
    {
        $dir = $this->resolveAppSeedersPath();
        return $dir !== null ? glob($dir . '/*.php') : [];
    }

    private function getTenantSeederFiles(): array
    {
        $dir = $this->resolveAppSeedersPath();
        if ($dir === null) {
            return [];
        }
        $tenantDir = $dir . '/Tenants';
        return is_dir($tenantDir) ? glob($tenantDir . '/*.php') : [];
    }

    private function resolveAppSeedersPath(): ?string
    {
        if ($this->structureResolver) {
            try {
                $path = $this->structureResolver->getAppPath('seeders');
                $fullPath = BASE_PATH . '/' . $path;
                return is_dir($fullPath) ? $fullPath : null;
            } catch (\InvalidArgumentException $e) {
                return $this->getDefaultAppSeedersPath();
            }
        }

        return $this->getDefaultAppSeedersPath();
    }

    private function getDefaultAppSeedersPath(): ?string
    {
        try {
            $resolver = $this->structureResolver ?? new StructureResolver();
            $path = $resolver->getAppPath('seeders');
            $fullPath = BASE_PATH . '/' . $path;
            return is_dir($fullPath) ? $fullPath : null;
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    private function getModuleSeeders(?string $target = null): array
    {
        if (!is_dir($this->getModulesPath())) {
            return [];
        }

        $result = [];
        foreach (scandir($this->getModulesPath()) as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }

            if ($target && $module !== $target) {
                continue;
            }

            if (ModuleHelper::isModuleDisabled($module)) {
                continue;
            }

            $path = $this->resolveModuleSeedersPath($module);
            if ($path !== null && is_dir($path)) {
                $result = array_merge($result, glob($path . '/*.php'));
            }
        }

        return $result;
    }

    private function resolveModuleSeedersPath(string $moduleName): ?string
    {
        if ($this->structureResolver) {
            try {
                $path = $this->structureResolver->getModulePath($moduleName, 'seeders');
                $fullPath = $this->getModulesPath() . '/' . $moduleName . '/' . $path;
                return is_dir($fullPath) ? $fullPath : null;
            } catch (\InvalidArgumentException $e) {
                return $this->getDefaultModuleSeedersPath($moduleName);
            }
        }

        return $this->getDefaultModuleSeedersPath($moduleName);
    }

    private function getDefaultModuleSeedersPath(string $moduleName): ?string
    {
        try {
            $resolver = $this->structureResolver ?? new StructureResolver();
            $path = $resolver->getModulePath($moduleName, 'seeders');
            $fullPath = $this->getModulesPath() . '/' . $moduleName . '/' . $path;
            return is_dir($fullPath) ? $fullPath : null;
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    private function getAllAppSeederFiles(): array
    {
        return $this->getAppSeederFiles();
    }

    private function getAllTenantSeederFiles(): array
    {
        return $this->getTenantSeederFiles();
    }

    private function getAllModuleSeederFiles(?string $target = null): array
    {
        return $this->getModuleSeeders($target);
    }

    /**
     * @throws ReflectionException
     */
    private function runSeeder(string $path): void
    {
        $instance = $this->resolveSeeder($path);
        $instance->up();

        $stmt = $this->connection->prepare(
            "INSERT INTO " . self::SEEDERS_TABLE . " (seeder, batch) VALUES (?, ?)"
        );
        $stmt->execute([basename($path), $this->getNextBatchNumber()]);
    }

    /**
     * @throws ReflectionException
     */
    private function resolveSeeder(string $path): object|string
    {
        require_once $path;
        $className = $this->getSeederClassName($path);

        if (!class_exists($className)) {
            throw new RuntimeException("Seeder class $className not found in $path");
        }

        $reflection = new \ReflectionClass($className);
        if (!$reflection->isSubclassOf(Seeder::class)) {
            throw new RuntimeException("Invalid seeder class: $className");
        }

        return $reflection->newInstance($this->connection);
    }

    private function getSeederClassName(string $path): string
    {
        $contents = @file_get_contents($path);
        $namespace = '';
        if ($contents !== false && preg_match('/namespace\s+([^;]+);/', $contents, $match)) {
            $namespace = trim($match[1]) . '\\';
        }

        return $namespace . preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', basename($path, '.php'));
    }

    private function getNextBatchNumber(): int
    {
        $stmt = $this->connection->query("SELECT MAX(batch) FROM " . self::SEEDERS_TABLE);
        return (int) $stmt->fetchColumn() + 1;
    }

    public function rollback(int $steps = 1): void
    {
        $this->connection->beginTransaction();
        try {
            $ran = $this->getRanSeeders($steps);
            foreach ($ran as $seeder) {
                $this->rollbackSeeder($seeder);
            }
            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function getRanSeeders(int $steps): array
    {
        $batch = $this->getLastBatch() - $steps + 1;
        $stmt = $this->connection->prepare(
            "SELECT seeder FROM " . self::SEEDERS_TABLE . " WHERE batch >= ? ORDER BY batch DESC, seeder DESC"
        );
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getLastBatch(): int
    {
        $stmt = $this->connection->query("SELECT MAX(batch) FROM " . self::SEEDERS_TABLE);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @throws ReflectionException
     */
    private function rollbackSeeder(string $filename): void
    {
        $path = $this->findSeederPath($filename);
        if (!$path) {
            $this->connection->prepare(
                "DELETE FROM " . self::SEEDERS_TABLE . " WHERE seeder = ?"
            )->execute([$filename]);
            return;
        }

        try {
            $this->resolveSeeder($path)->down();
        } catch (Throwable $e) {
        }

        $this->connection->prepare(
            "DELETE FROM " . self::SEEDERS_TABLE . " WHERE seeder = ?"
        )->execute([$filename]);
    }

    private function findSeederPath(string $filename): ?string
    {
        $paths = array_merge(
            $this->getAllAppSeederFiles(),
            $this->getAllTenantSeederFiles(),
            $this->getAllModuleSeederFiles()
        );

        foreach ($paths as $path) {
            if (basename($path) === $filename) {
                return $path;
            }
        }
        return null;
    }

    public function getSeedersForRollback(int $steps): array
    {
        $batch = $this->getLastBatch() - $steps + 1;
        $stmt = $this->connection->prepare(
            "SELECT seeder FROM " . self::SEEDERS_TABLE . " WHERE batch >= ? ORDER BY batch DESC, seeder DESC"
        );
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Retrieves all ran seeders with their batch number and run timestamp for preview.
     *
     * @return array<string, array{batch: int, ran_at: string}> Associative array where key is seeder name.
     */
    public function getAllRanSeedersWithDetails(): array
    {
        $stmt = $this->connection->query(
            "SELECT seeder, batch, ran_at FROM " . self::SEEDERS_TABLE . " ORDER BY ran_at DESC"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $list = [];
        foreach ($rows as $row) {
            $list[$row['seeder']] = [
                'batch' => $row['batch'],
                'ran_at' => $row['ran_at']
            ];
        }

        return $list;
    }

    public function discoverSeeders(?string $type = null, ?string $module = null): array
    {
        $seeders = [];

        if ($type === null || $type === 'app' || $type === 'all') {
            foreach ($this->getAllAppSeederFiles() as $file) {
                $seeders[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'source' => 'app',
                ];
            }
        }

        if ($type === null || $type === 'tenants' || $type === 'all') {
            foreach ($this->getAllTenantSeederFiles() as $file) {
                $seeders[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'source' => 'tenants',
                ];
            }
        }

        if ($type === null || $type === 'module' || $type === 'all') {
            foreach ($this->getAllModuleSeederFiles($module) as $file) {
                $seeders[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'source' => 'modules',
                ];
            }
        }

        return $seeders;
    }
}
