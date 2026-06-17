<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Services;

use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Forge\Core\DI\Attributes\Migration as MigrationAttribute;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Helpers\ModuleHelper;
use Forge\Core\Services\AttributeDiscoveryService;
use Forge\Core\Structure\StructureResolver;
use Forge\Traits\StringHelper;
use ReflectionClass;
use ReflectionException;

#[Service]
final class MigrationDiscoveryService
{
    use StringHelper;

    private const string CORE_MIGRATIONS_PATH = BASE_PATH . "/kernel/Database/Migrations";
    private const string MODULES_PATH = BASE_PATH . "/modules";

    public function __construct(
        private readonly ?StructureResolver $structureResolver = null
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
        $paths = $this->getMigrationPaths($scope, $module);
        $files = [];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = array_merge($files, glob($path . "/*.php"));
            }
        }

        return $files;
    }

    private function getMigrationPaths(?string $scope, ?string $module): array
    {
        $paths = [];

        switch ($scope) {
            case "core":
                $paths[] = self::CORE_MIGRATIONS_PATH;
                break;
            case "app":
                $paths[] = $this->getAppMigrationsPath();
                break;
            case "module":
                $paths = $this->getModuleMigrationPaths($module);
                break;
            case "all":
                $paths[] = self::CORE_MIGRATIONS_PATH;
                $paths[] = $this->getAppMigrationsPath();
                $paths = array_merge($paths, $this->getModuleMigrationPaths(null));
                break;
        }

        return $paths;
    }

    private function getAppMigrationsPath(): string
    {
        if ($this->structureResolver) {
            try {
                $appMigrationsPath = $this->structureResolver->getAppPath("migrations");
                return BASE_PATH . "/" . $appMigrationsPath;
            } catch (\InvalidArgumentException $e) {
                return BASE_PATH . "/app/Database/Migrations";
            }
        }
        return BASE_PATH . "/app/Database/Migrations";
    }

    private function getModuleMigrationPaths(?string $target): array
    {
        if (!is_dir(self::MODULES_PATH)) {
            return [];
        }

        $modules = $target
            ? [$target]
            : array_filter(scandir(self::MODULES_PATH), function ($item) {
                return is_dir(self::MODULES_PATH . "/" . $item) &&
                    !in_array($item, [".", ".."]);
            });

        $paths = [];
        foreach ($modules as $moduleName) {
            if (ModuleHelper::isModuleDisabled($moduleName)) {
                continue;
            }

            $modulePaths = $this->getModuleMigrationDirectories($moduleName);
            $paths = array_merge($paths, $modulePaths);
        }

        return $paths;
    }

    private function getModuleMigrationDirectories(string $moduleName): array
    {
        $paths = [];

        if ($this->structureResolver) {
            try {
                $moduleMigrationsPath = $this->structureResolver->getModulePath($moduleName, "migrations");
                $central = self::MODULES_PATH . "/" . $moduleName . "/" . $moduleMigrationsPath;

                if (is_dir($central)) {
                    $paths[] = $central;
                }

                $tenant = $central . "/Tenants";
                if (is_dir($tenant)) {
                    $paths[] = $tenant;
                }
            } catch (\InvalidArgumentException $e) {
                $paths = $this->getDefaultModulePaths($moduleName);
            }
        } else {
            $paths = $this->getDefaultModulePaths($moduleName);
        }

        return $paths;
    }

    private function getDefaultModulePaths(string $moduleName): array
    {
        $paths = [];
        $central = self::MODULES_PATH . "/" . $moduleName . "/src/Database/Migrations";

        if (is_dir($central)) {
            $paths[] = $central;
        }

        $tenant = $central . "/Tenants";
        if (is_dir($tenant)) {
            $paths[] = $tenant;
        }

        return $paths;
    }

    private function discoverAttributeBasedMigrations(?string $scope, ?string $module): array
    {
        $discoveryService = new AttributeDiscoveryService();
        $basePaths = $this->getBasePathsForDiscovery($scope, $module);

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
                            if ($this->matchesScopeAndModule($filepath, $scope, $module)) {
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

    private function getBasePathsForDiscovery(?string $scope, ?string $module): array
    {
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

    private function matchesScopeAndModule(string $filepath, ?string $scope, ?string $module): bool
    {
        $relativePath = str_replace(BASE_PATH . "/", "", $filepath);

        if ($scope === "core") {
            return str_starts_with($relativePath, "kernel/");
        }

        if ($scope === "app") {
            return $this->matchesAppPath($relativePath);
        }

        if ($scope === "module" && $module) {
            return $this->matchesModulePath($relativePath, $module);
        }

        return true;
    }

    private function matchesAppPath(string $relativePath): bool
    {
        if ($this->structureResolver) {
            try {
                $appMigrationsPath = $this->structureResolver->getAppPath("migrations");
                return str_starts_with($relativePath, $appMigrationsPath);
            } catch (\InvalidArgumentException $e) {
                return str_starts_with($relativePath, "app/Database/Migrations");
            }
        }
        return str_starts_with($relativePath, "app/");
    }

    private function matchesModulePath(string $relativePath, string $module): bool
    {
        $modulePath = "modules/$module/";
        if (!str_starts_with($relativePath, $modulePath)) {
            return false;
        }

        if ($this->structureResolver) {
            try {
                $moduleMigrationsPath = $this->structureResolver->getModulePath($module, "migrations");
                $expectedPath = "$modulePath$moduleMigrationsPath";
                return str_starts_with($relativePath, $expectedPath);
            } catch (\InvalidArgumentException $e) {
                return str_starts_with($relativePath, "$modulePath" . "src/Database/Migrations");
            }
        }

        return true;
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
            $className = $this->getMigrationClassName($path);
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

    private function getMigrationClassName(string $path): string
    {
        $filename = basename($path, ".php");
        return preg_replace("/^\d{4}_\d{2}_\d{2}_\d{6}_/", "", $filename);
    }
}
