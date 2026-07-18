<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Services;

use Forge\Core\DI\Attributes\Injectable;
use Forge\Core\Helpers\ModuleHelper;
use Forge\Core\Structure\StructureResolver;
use Forge\Traits\StringHelper;

#[Injectable]
final class MigrationPathResolverService
{
    use StringHelper;

    public function __construct(
        private readonly ?StructureResolver $structureResolver = null
    ) {
    }

    private function getModulesRoot(): string
    {
        return $this->structureResolver?->getModulesRoot() ?? StructureResolver::resolveModulesRoot();
    }

    private function getModulesPath(): string
    {
        return BASE_PATH . '/' . $this->getModulesRoot();
    }

    /**
     * Get migration paths based on scope and module
     */
    public function getMigrationPaths(?string $scope = "all", ?string $module = null): array
    {
        switch ($scope) {
            case "app":
                return $this->getAppPaths();
            case "module":
                return $this->getModulePaths($module);
            case "all":
            default:
                return $this->getAllPaths();
        }
    }

    /**
     * Get app migration paths
     */
    public function getAppPaths(): array
    {
        $appPath = $this->resolveAppMigrationsPath();
        return $appPath !== null ? [$appPath] : [];
    }

    /**
     * Get module migration paths
     */
    public function getModulePaths(?string $module = null): array
    {
        if (!is_dir($this->getModulesPath())) {
            return [];
        }

        $modules = $module
            ? [$this->toPascalCase($module)]
            : $this->getAvailableModules();

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

    /**
     * Get all migration paths (app + modules)
     */
    public function getAllPaths(): array
    {
        $paths = array_merge(
            $this->getAppPaths(),
            $this->getModulePaths()
        );

        return array_unique($paths);
    }

    /**
     * Check if a path matches the given scope and module
     */
    public function matchesScopeAndModule(string $filepath, ?string $scope, ?string $module): bool
    {
        $relativePath = str_replace(BASE_PATH . "/", "", $filepath);

        if ($scope === "app") {
            return $this->matchesAppPath($relativePath);
        }

        if ($scope === "module" && $module) {
            return $this->matchesModulePath($relativePath, $module);
        }

        return true;
    }

    /**
     * Extract migration type from path
     */
    public function extractTypeFromPath(string $path): string
    {
        $relativePath = str_replace(BASE_PATH . "/", "", $path);

        if (str_starts_with($relativePath, $this->getModulesRoot() . "/")) {
            return "module";
        }

        return "app";
    }

    /**
     * Extract module name from path
     */
    public function extractModuleFromPath(string $path): ?string
    {
        $relativePath = str_replace(BASE_PATH . "/", "", $path);

        if (preg_match("/^" . preg_quote($this->getModulesRoot(), '/') . "\/([^\/]+)\//", $relativePath, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Resolve app migrations path using StructureResolver or fallback
     */
    private function resolveAppMigrationsPath(): ?string
    {
        if ($this->structureResolver) {
            try {
                $appMigrationsPath = $this->structureResolver->getAppPath("migrations");
                $fullPath = BASE_PATH . "/" . $appMigrationsPath;
                return is_dir($fullPath) ? $fullPath : null;
            } catch (\InvalidArgumentException $e) {
                return $this->getDefaultAppPath();
            }
        }

        return $this->getDefaultAppPath();
    }

    private function getDefaultAppPath(): ?string
    {
        try {
            $resolver = $this->structureResolver ?? new StructureResolver();
            $migrationsPath = $resolver->getAppPath('migrations');
            $fullPath = BASE_PATH . '/' . $migrationsPath;
            return is_dir($fullPath) ? $fullPath : null;
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Get available modules (non-disabled)
     */
    private function getAvailableModules(): array
    {
        $modulesPath = $this->getModulesPath();
        return array_filter(scandir($modulesPath), function ($item) use ($modulesPath) {
            return is_dir($modulesPath . "/" . $item) &&
                !in_array($item, [".", ".."]);
        });
    }

    /**
     * Get migration directories for a specific module
     */
    private function getModuleMigrationDirectories(string $moduleName): array
    {
        if ($this->structureResolver) {
            try {
                return $this->getStructuredModulePaths($moduleName);
            } catch (\InvalidArgumentException $e) {
                return $this->getDefaultModulePaths($moduleName);
            }
        }

        return $this->getDefaultModulePaths($moduleName);
    }

    /**
     * Get module paths using StructureResolver
     */
    private function getStructuredModulePaths(string $moduleName): array
    {
        $paths = [];
        $moduleMigrationsPath = $this->structureResolver->getModulePath($moduleName, "migrations");

        $central = $this->getModulesPath() . "/" . $moduleName . "/" . $moduleMigrationsPath;
        if (is_dir($central)) {
            $paths[] = $central;
        }

        $tenant = $central . "/Tenants";
        if (is_dir($tenant)) {
            $paths[] = $tenant;
        }

        return $paths;
    }

    /**
     * Get default module paths
     */
    private function getDefaultModulePaths(string $moduleName): array
    {
        $paths = [];
        try {
            $resolver = $this->structureResolver ?? new StructureResolver();
            $moduleMigrationsPath = $resolver->getModulePath($moduleName, 'migrations');
            $central = $this->getModulesPath() . '/' . $moduleName . '/' . $moduleMigrationsPath;
        } catch (\InvalidArgumentException $e) {
            return [];
        }

        if (is_dir($central)) {
            $paths[] = $central;
        }

        $tenant = $central . "/Tenants";
        if (is_dir($tenant)) {
            $paths[] = $tenant;
        }

        return $paths;
    }

    /**
     * Check if relative path matches app migration path
     */
    private function matchesAppPath(string $relativePath): bool
    {
        try {
            $resolver = $this->structureResolver ?? new StructureResolver();
            $appMigrationsPath = $resolver->getAppPath('migrations');
            return str_starts_with($relativePath, $appMigrationsPath);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Check if relative path matches module path
     */
    private function matchesModulePath(string $relativePath, string $module): bool
    {
        $modulePath = $this->getModulesRoot() . "/" . $this->toPascalCase($module) . "/";
        if (!str_starts_with($relativePath, $modulePath)) {
            return false;
        }

        try {
            $resolver = $this->structureResolver ?? new StructureResolver();
            $moduleMigrationsPath = $resolver->getModulePath($module, "migrations");
            $expectedPath = "$modulePath$moduleMigrationsPath";
            return str_starts_with($relativePath, $expectedPath);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Get base paths for migration discovery using configured structure paths.
     * Returns migration directories relative to BASE_PATH so attribute discovery
     * only scans the folders the user/module actually configured.
     */
    public function getBasePathsForDiscovery(?string $scope, ?string $module): array
    {
        $paths = $this->getMigrationPaths($scope, $module);

        return array_map(
            fn(string $path): string => str_replace(BASE_PATH . "/", "", $path),
            $paths,
        );
    }

    /**
     * Get migration file name from path
     */
    public function getMigrationFileName(string $path): string
    {
        return basename($path);
    }

    /**
     * Get migration class name from path
     */
    public function getMigrationClassName(string $path): string
    {
        $filename = basename($path, ".php");
        return preg_replace("/^\d{4}_\d{2}_\d{2}_\d{6}_/", "", $filename);
    }
}
