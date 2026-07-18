<?php

declare(strict_types=1);

namespace Modules\ForgeTemplates;

use Forge\Core\Helpers\ModuleHelper;
use Forge\Core\Structure\StructureResolver;
use RuntimeException;

final class TemplateFinder
{
    private static array $pathCache = [];

    public function __construct(
        private readonly StructureResolver $structureResolver,
        private readonly string $appTemplatePath,
    ) {
    }

    public function find(string $template): string
    {
        $cacheKey = "template|" . $template . "|" . $this->appTemplatePath;

        if (isset(self::$pathCache[$cacheKey])) {
            return self::$pathCache[$cacheKey];
        }

        $resolvedPath = null;

        if (str_contains($template, ":")) {
            [$module, $relative] = explode(":", $template, 2);
            if (ModuleHelper::isModuleDisabled($module)) {
                throw new RuntimeException("Template not found: {$template} (Module {$module} is disabled)");
            }

            $resolvedPath = $this->resolveModuleTemplatePath($module, $relative);
        }

        if ($resolvedPath === null) {
            $file = "{$this->appTemplatePath}/{$template}.php";

            if (!is_file($file)) {
                throw new RuntimeException("Template not found: {$template} (Searched in: {$file})");
            }

            $resolvedPath = $file;
        }

        self::$pathCache[$cacheKey] = $resolvedPath;
        return $resolvedPath;
    }

    public function findLayout(string $layout): string
    {
        $cacheKey = "layout|" . $layout . "|" . $this->appTemplatePath;

        if (isset(self::$pathCache[$cacheKey])) {
            return self::$pathCache[$cacheKey];
        }

        $file = "{$this->appTemplatePath}/layouts/{$layout}.php";

        if (!is_file($file)) {
            throw new RuntimeException("Layout not found: {$layout} (Searched in: {$file})");
        }

        self::$pathCache[$cacheKey] = $file;
        return $file;
    }

    private function resolveModuleTemplatePath(string $module, string $relative): ?string
    {
        foreach (StructureResolver::resolveModulesRoots() as $root) {
            $modulesRoot = BASE_PATH . '/' . $root;
            $moduleDir = "{$modulesRoot}/{$module}";
            if (!is_dir($moduleDir)) {
                continue;
            }

            try {
                $moduleTemplatesPath = $this->structureResolver->getModulePath($module, "templates");
                $modulePath = "{$moduleDir}/{$moduleTemplatesPath}/{$relative}.php";

                if (is_file($modulePath)) {
                    return $modulePath;
                }
            } catch (\InvalidArgumentException $e) {
                if (function_exists('collect_exception')) {
                    collect_exception($e);
                }
            }
        }

        return null;
    }
}
