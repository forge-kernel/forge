<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Services;

use Modules\ForgeDatabaseSQL\DB\Migrations\Migration;
use Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Forge\Core\DI\Attributes\Migration as MigrationAttribute;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Helpers\ModuleHelper;
use Forge\Core\Services\AttributeDiscoveryService;
use Forge\Traits\StringHelper;
use ReflectionClass;
use ReflectionException;

#[Service]
final class MigrationDiscoveryService
{
    use StringHelper;

    private const string MODULES_PATH = BASE_PATH . "/modules";

    public function __construct(
        private readonly MigrationPathResolverService $pathResolver,
    ) {
    }

    /**
     * Discover all migration files based on scope and filters
     */
    public function discoverMigrationFiles(
        ?string $scope = "all",
        ?string $module = null
    ): array {
        $files = $this->resolveMigrationFiles($scope, $module);
        $attributeFiles = $this->discoverAttributeBasedMigrations($scope, $module);

        $allFiles = array_unique(array_merge($files, $attributeFiles));
        sort($allFiles);

        return $allFiles;
    }

    /**
     * Get pending migrations by filtering discovered files against ran migrations
     */
    public function getPendingMigrations(
        array $ranMigrations,
        ?string $scope = "all",
        ?string $module = null,
        ?string $group = null
    ): array {
        $allFiles = $this->discoverMigrationFiles($scope, $module);

        $ranLookup = [];
        foreach ($ranMigrations as $migration) {
            $ranLookup[strtolower(basename($migration))] = true;
        }

        $pendingFiles = [];
        foreach ($allFiles as $path) {
            $migrationName = basename($path);

            if (isset($ranLookup[strtolower($migrationName)])) {
                continue;
            }

            if ($module !== null) {
                $relativePath = str_replace(BASE_PATH . "/", "", $path);
                if (!$this->matchesModuleFilter($relativePath, $module)) {
                    continue;
                }
            }

            if ($group !== null) {
                $migrationGroup = $this->extractGroupFromPath($path);
                if ($migrationGroup !== $group) {
                    continue;
                }
            }

            $pendingFiles[] = $path;
        }

        return $pendingFiles;
    }

    private function resolveMigrationFiles(?string $scope, ?string $module): array
    {
        $paths = $this->pathResolver->getMigrationPaths($scope, $module);
        $files = [];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = array_merge($files, glob($path . "/*.php"));
            }
        }

        return $files;
    }

    private function discoverAttributeBasedMigrations(?string $scope, ?string $module): array
    {
        $discoveryService = new AttributeDiscoveryService();
        $basePaths = $this->pathResolver->getBasePathsForDiscovery($scope, $module);

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
                        if ($filepath && FileExistenceCache::exists($filepath)) {
                            if ($this->pathResolver->matchesScopeAndModule($filepath, $scope, $module)) {
                                $files[] = $filepath;
                            }
                        }
                    }
                } catch (ReflectionException $e) {
                    continue;
                }
            }
        }

        return $files;
    }

    private function matchesModuleFilter(string $relativePath, string $module): bool
    {
        $modulePath = "modules/" . $this->toPascalCase($module) . "/";
        return str_starts_with($relativePath, $modulePath);
    }

    private function extractGroupFromPath(string $path): ?string
    {
        try {
            require_once $path;
            $className = $this->pathResolver->getMigrationClassName($path);
            $reflection = new ReflectionClass($className);

            $attributes = $reflection->getAttributes(GroupMigration::class);
            if (!empty($attributes)) {
                $instance = $attributes[0]->newInstance();
                return $instance->name ?? null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
