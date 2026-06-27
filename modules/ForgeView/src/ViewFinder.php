<?php

declare(strict_types=1);

namespace App\Modules\ForgeView;

use Forge\Core\Helpers\ModuleHelper;
use Forge\Core\Structure\StructureResolver;
use RuntimeException;

final class ViewFinder
{
    private static array $pathCache = [];
    private static array $componentPathCache = [];

    public function __construct(
        private readonly ?StructureResolver $structureResolver,
        private readonly string $appViewPath,
        private readonly string $appComponentPath,
    ) {}

    public function findView(string $view, ?string $moduleContext = null): string
    {
        $cacheKey = "view|" . $view . "|" . $this->appViewPath . "|" . ($moduleContext ?? "");

        if (isset(self::$pathCache[$cacheKey])) {
            return self::$pathCache[$cacheKey];
        }

        $resolvedPath = null;

        if (str_contains($view, ":")) {
            [$module, $relative] = explode(":", $view, 2);
            if (ModuleHelper::isModuleDisabled($module)) {
                throw new RuntimeException("View file not found: {$view} (Module {$module} is disabled)");
            }

            $resolvedPath = $this->resolveModuleViewPath($module, $relative);
        } elseif ($moduleContext) {
            if (ModuleHelper::isModuleDisabled($moduleContext)) {
                throw new RuntimeException("View file not found: {$view} (Module {$moduleContext} is disabled)");
            }

            $resolvedPath = $this->resolveModuleViewPath($moduleContext, $view);
        }

        if ($resolvedPath === null) {
            $file = "{$this->appViewPath}/{$view}.php";

            if (!is_file($file)) {
                throw new RuntimeException("View file not found: {$view} (Searched in: {$file})");
            }

            $resolvedPath = $file;
        }

        self::$pathCache[$cacheKey] = $resolvedPath;
        return $resolvedPath;
    }

    public function findLayout(string $layoutName, ?string $moduleContext = null): string
    {
        if (str_contains($layoutName, ":")) {
            [$module, $layout] = explode(":", $layoutName, 2);
            return $this->findView("{$module}:layouts/{$layout}");
        }

        $file = "{$this->appViewPath}/layouts/{$layoutName}.php";
        if (!is_file($file)) {
            $globalViewPath = $this->getGlobalAppViewPath();
            $file = "{$globalViewPath}/layouts/{$layoutName}.php";
        }

        if (!is_file($file)) {
            throw new RuntimeException("View file not found: layouts/{$layoutName} (Searched in: {$this->appViewPath}/layouts/ and {$globalViewPath}/layouts/)");
        }

        return $file;
    }

    private function getGlobalAppViewPath(): string
    {
        $basePath = defined("BASE_PATH") ? BASE_PATH : dirname(__DIR__, 5);

        $path = "app/UI/views";
        if ($this->structureResolver) {
            try {
                $path = $this->structureResolver->getAppPath("views");
            } catch (\InvalidArgumentException $e) {
                // Ignore
            }
        }
        
        if (str_starts_with($path, 'app/') && !is_dir($basePath . "/" . $path)) {
            $altPath = 'src/' . substr($path, 4);
            if (is_dir($basePath . "/" . $altPath)) {
                $path = $altPath;
            }
        }

        return $basePath . "/" . $path;
    }

    public function findComponent(string $component, ?string $moduleContext = null): string
    {
        $cacheKey = "component|" . $component . "|" . ($moduleContext ?? "") . "|" . $this->appComponentPath;

        if (isset(self::$componentPathCache[$cacheKey])) {
            return self::$componentPathCache[$cacheKey];
        }

        $resolvedPath = null;

        if (str_contains($component, ":")) {
            [$moduleName, $relative] = explode(":", $component, 2);
            if (ModuleHelper::isModuleDisabled($moduleName)) {
                throw new RuntimeException("Module component template not found: {$relative} in module {$moduleName} (Module is disabled)");
            }

            $resolvedPath = $this->resolveModuleComponentPath($moduleName, $relative);
        } elseif ($moduleContext) {
            if (ModuleHelper::isModuleDisabled($moduleContext)) {
                throw new RuntimeException("Component template not found: {$component} (Module {$moduleContext} is disabled)");
            }

            $resolvedPath = $this->resolveModuleComponentPath($moduleContext, $component);
        }

        if ($resolvedPath === null) {
            $file = "{$this->appComponentPath}/{$component}.php";
            if (is_file($file)) {
                $resolvedPath = $file;
            }
        }

        if ($resolvedPath === null) {
            throw new RuntimeException("Component template not found: {$component}");
        }

        self::$componentPathCache[$cacheKey] = $resolvedPath;
        return $resolvedPath;
    }

    private function resolveModuleViewPath(string $module, string $relative): ?string
    {
        if ($this->structureResolver) {
            try {
                $moduleViewsPath = $this->structureResolver->getModulePath($module, "views");
                $modulePath = BASE_PATH . "/modules/{$module}/{$moduleViewsPath}/{$relative}.php";
                
                $fileCacheKey = "file_exists:" . $modulePath;
                if (!isset(self::$componentPathCache[$fileCacheKey])) {
                    self::$componentPathCache[$fileCacheKey] = is_file($modulePath);
                }
                if (self::$componentPathCache[$fileCacheKey]) {
                    return $modulePath;
                }
            } catch (\InvalidArgumentException $e) {
                // Ignore and fall through to fallback
            }
        }

        foreach (["UI"] as $res) {
            $modulePath = BASE_PATH . "/modules/{$module}/src/{$res}/views/{$relative}.php";
            $fileCacheKey = "file_exists:" . $modulePath;
            if (!isset(self::$componentPathCache[$fileCacheKey])) {
                self::$componentPathCache[$fileCacheKey] = is_file($modulePath);
            }
            if (self::$componentPathCache[$fileCacheKey]) {
                return $modulePath;
            }
        }

        return null;
    }

    private function resolveModuleComponentPath(string $module, string $relative): ?string
    {
        $paths = $this->buildModulePaths($module, $relative, "components");

        foreach ($paths as $file) {
            $fileCacheKey = "file_exists:" . $file;
            if (!isset(self::$componentPathCache[$fileCacheKey])) {
                self::$componentPathCache[$fileCacheKey] = is_file($file);
            }
            if (self::$componentPathCache[$fileCacheKey]) {
                return $file;
            }
        }

        return null;
    }

    private function buildModulePaths(string $module, string $relative, string $type): array
    {
        $paths = [];

        if ($this->structureResolver) {
            try {
                $modulePath = $this->structureResolver->getModulePath($module, $type);
                $paths[] = BASE_PATH . "/modules/{$module}/{$modulePath}/{$relative}.php";

                if ($type === "components") {
                    $moduleViewsPath = $this->structureResolver->getModulePath($module, "views");
                    $paths[] = BASE_PATH . "/modules/{$module}/{$moduleViewsPath}/components/{$relative}.php";
                }
            } catch (\InvalidArgumentException $e) {
                // Ignore
            }
        }

        foreach (["UI"] as $res) {
            $paths[] = BASE_PATH . "/modules/{$module}/src/{$res}/{$type}/{$relative}.php";

            if ($type === "components") {
                $paths[] = BASE_PATH . "/modules/{$module}/src/{$res}/views/components/{$relative}.php";
            }
        }

        return array_unique($paths);
    }
}
