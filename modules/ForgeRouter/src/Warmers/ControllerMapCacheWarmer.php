<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Warmers;

use Forge\Core\Bootstrap\OptimizedDirectoryScanner;
use Forge\Core\Config\Config;
use Forge\Core\Contracts\Cache\CacheWarmerInterface;
use Forge\Core\DI\Attributes\Injectable;
use Forge\Core\DI\Container;
use Forge\Core\Structure\StructureResolver;
use Modules\ForgeRouter\Routing\ControllerLoader;

#[Injectable]
final class ControllerMapCacheWarmer implements CacheWarmerInterface
{
    private const ROUTE_CACHE_FILE = BASE_PATH . '/storage/framework/cache/controller-map.php';

    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function warmCache(): void
    {
        $structureResolver = $this->container->has(StructureResolver::class)
            ? $this->container->get(StructureResolver::class)
            : new StructureResolver();

        $config = $this->container->has(Config::class)
            ? $this->container->get(Config::class)
            : null;

        $controllerDirs = $this->resolveControllerDirs($structureResolver, $config);

        $loader = new ControllerLoader($this->container, $controllerDirs);
        $controllers = $loader->registerControllers();

        $this->writeCache($controllers);
    }

    private function resolveControllerDirs(StructureResolver $structureResolver, ?Config $config): array
    {
        $dirs = [];

        foreach ($structureResolver->getAppPaths('controllers') as $path) {
            $fullPath = BASE_PATH . '/' . $path;
            if (is_dir($fullPath)) {
                $dirs[] = [
                    'path' => $fullPath,
                    'namespace' => $structureResolver->getAppNamespace('controllers'),
                ];
            }
        }

        foreach ($structureResolver->getAppPaths('http') as $path) {
            $fullPath = BASE_PATH . '/' . $path;
            if (is_dir($fullPath)) {
                $dirs[] = [
                    'path' => $fullPath,
                    'namespace' => $structureResolver->getAppNamespace('http'),
                ];
            }
        }

        $moduleDirs = OptimizedDirectoryScanner::getModuleDirectories($config);
        foreach ($moduleDirs as $moduleName => $modulePath) {
            if (!is_dir($modulePath . '/src/Controllers') && !is_dir($modulePath . '/src/Http')) {
                continue;
            }

            if (is_dir($modulePath . '/src/Controllers')) {
                $dirs[] = [
                    'path' => $modulePath . '/src/Controllers',
                    'namespace' => $structureResolver->getModuleNamespace($moduleName, 'controllers'),
                ];
            }

            if (is_dir($modulePath . '/src/Http')) {
                $dirs[] = [
                    'path' => $modulePath . '/src/Http',
                    'namespace' => $structureResolver->getModuleNamespace($moduleName, 'http'),
                ];
            }
        }

        return $dirs;
    }

    private function writeCache(array $controllers): void
    {
        $dir = dirname(self::ROUTE_CACHE_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $export = var_export([
            'controllers' => $controllers,
        ], true);

        $tmp = tempnam($dir, 'ctrl_');
        if ($tmp !== false) {
            file_put_contents($tmp, '<?php return ' . $export . ';');
            @chmod($tmp, 0664);
            rename($tmp, self::ROUTE_CACHE_FILE);
        } else {
            file_put_contents(self::ROUTE_CACHE_FILE, '<?php return ' . $export . ';');
        }
    }
}
