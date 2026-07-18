<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Services;

use Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Forge\Core\Structure\StructureResolver;
use Forge\Traits\StringHelper;
use ReflectionClass;
use ReflectionException;

final class MigrationDiscoveryService
{
    use StringHelper;

    public function __construct(
        private readonly MigrationPathResolverService $pathResolver,
        private readonly ?StructureResolver $structureResolver = null,
    ) {
    }

    /**
     * Discover all migration files based on scope and filters
     */
    public function discoverMigrationFiles(
        ?string $scope = "all",
        ?string $module = null
    ): array {
        $paths = $this->pathResolver->getMigrationPaths($scope, $module);
        $files = [];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = array_merge($files, glob($path . "/*.php"));
            }
        }

        sort($files);

        return $files;
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

    private function matchesModuleFilter(string $relativePath, string $module): bool
    {
        $modulesRoot = $this->structureResolver?->getModulesRoot() ?? 'modules';
        $modulePath = $modulesRoot . "/" . $this->toPascalCase($module) . "/";
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
