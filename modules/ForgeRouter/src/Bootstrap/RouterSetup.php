<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Bootstrap;

use Forge\Core\Debug\Metrics;
use Forge\Core\DI\Container;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Helpers\ModuleHelper;
use Modules\ForgeRouter\Routing\ControllerLoader;
use Modules\ForgeRouter\Routing\Router;
use Modules\ForgeRouter\ForgeRouterModule;
use Forge\Core\Structure\StructureResolver;
use Forge\Exceptions\MissingServiceException;
use ReflectionException;

final class RouterSetup
{
    private const ROUTE_CACHE_FILE = BASE_PATH . '/storage/framework/cache/controller-map.php';

    /**
     * @throws ReflectionException|MissingServiceException
     */
    public static function setup(Container $container): Router
    {
        return self::initRouter($container);
    }

    /**
     * Initializes the router, loads controllers, and prepares middleware.
     *
     * @throws ReflectionException|MissingServiceException
     */
    private static function initRouter(Container $container): Router
    {
        Metrics::start("router_controller_dir_scan");
        $structureResolver = $container->has(StructureResolver::class)
            ? $container->get(StructureResolver::class)
            : new StructureResolver();

        $controllerDirs = self::resolveControllerDirectories($structureResolver);
        Metrics::stop("router_controller_dir_scan");

        Metrics::start("router_controller_cache_load");
        $loader = new ControllerLoader($container, $controllerDirs);
        $cacheResult = self::loadControllersWithCache($loader);
        $controllers = $cacheResult['controllers'];
        $cachedRouteData = $cacheResult['routeData'];
        Metrics::stop("router_controller_cache_load");

        Metrics::start("router_middleware_loader");
        $autoLoadedMap = [];

        foreach (ForgeRouterModule::getRegisteredMiddleware() as $group => $items) {
            foreach ($items as $item) {
                $autoLoadedMap[$group][] = $item;
            }
        }

        foreach ($autoLoadedMap as &$items) {
            usort($items, fn($a, $b) => $a['order'] <=> $b['order']);
        }
        unset($items);
        Metrics::stop("router_middleware_loader");

        Metrics::start("router_middleware_merge");
        $appMiddlewareConfigFile = BASE_PATH . "/config/middleware.php";
        $appMiddlewareConfig = [];
        if (FileExistenceCache::exists($appMiddlewareConfigFile)) {
            $appMiddlewareConfig = require $appMiddlewareConfigFile;
            $appMiddlewareConfig = is_array($appMiddlewareConfig)
                ? $appMiddlewareConfig
                : [];
        }

        $finalMiddlewareConfig = $appMiddlewareConfig;

        foreach ($autoLoadedMap as $group => $middlewareData) {
            if (
                !isset($finalMiddlewareConfig[$group]) ||
                !is_array($finalMiddlewareConfig[$group])
            ) {
                $finalMiddlewareConfig[$group] = [];
            }

            $configMiddlewares = $finalMiddlewareConfig[$group];
            $currentGroup = array_flip($configMiddlewares);

            foreach ($middlewareData as $item) {
                if (is_string($item)) {
                    $item = ["class" => $item, "overrideClass" => null];
                }

                $autoClass = $item["class"] ?? null;
                $overrideClass = $item["overrideClass"] ?? null;

                if ($overrideClass) {
                    unset($currentGroup[$overrideClass]);
                }

                $currentGroup[$autoClass] = true;
            }

            $finalMiddlewareConfig[$group] = array_keys($currentGroup);
        }

        Metrics::stop("router_middleware_merge");

        Metrics::start("router_init");
        $router = Router::init($container, $finalMiddlewareConfig);
        Metrics::stop("router_init");

        Metrics::start("router_register_controllers");
        if ($cachedRouteData !== null && $cachedRouteData !== []) {
            foreach ($controllers as $controllerMeta) {
                $class = $controllerMeta['class'];
                if (isset($cachedRouteData[$class])) {
                    $router->registerCachedControllers($cachedRouteData[$class]);
                }
            }
        } else {
            $allRouteData = [];
            foreach ($controllers as $controllerMeta) {
                $class = $controllerMeta['class'];
                $routes = $router->registerControllers($class);
                $allRouteData[$class] = $routes;
            }
            self::writeControllerCache($controllers, $allRouteData);
        }
        Metrics::stop("router_register_controllers");

        return $router;
    }

    /**
     * @return array<int, array{path: string, namespace: string}>
     */
    private static function resolveControllerDirectories(StructureResolver $structureResolver): array
    {
        $dirs = [];

        foreach ($structureResolver->getAppPaths('controllers') as $path) {
            $fullPath = BASE_PATH . '/' . $path;
            if (is_dir($fullPath)) {
                $dirs[] = [
                    'path' => $fullPath,
                    'namespace' => $structureResolver->getAppNamespace('controllers', $path),
                ];
            }
        }

        foreach ($structureResolver->getAppPaths('http') as $path) {
            $fullPath = BASE_PATH . '/' . $path;
            if (is_dir($fullPath)) {
                $dirs[] = [
                    'path' => $fullPath,
                    'namespace' => $structureResolver->getAppNamespace('http', $path),
                ];
            }
        }

        foreach ($structureResolver->getModulesRoots() as $modulesRoot) {
            $modulesRoot = BASE_PATH . '/' . $modulesRoot;
            if (!is_dir($modulesRoot)) {
                continue;
            }
            foreach (scandir($modulesRoot) as $moduleName) {
                if ($moduleName === '.' || $moduleName === '..') {
                    continue;
                }
                if (ModuleHelper::isModuleDisabled($moduleName)) {
                    continue;
                }

                try {
                    foreach ($structureResolver->getModulePaths($moduleName, 'controllers') as $modulePath) {
                        $fullPath = $modulesRoot . '/' . $moduleName . '/' . $modulePath;
                        if (is_dir($fullPath)) {
                            $dirs[] = [
                                'path' => $fullPath,
                                'namespace' => $structureResolver->getModuleNamespace($moduleName, 'controllers'),
                            ];
                        }
                    }

                    foreach ($structureResolver->getModulePaths($moduleName, 'http') as $modulePath) {
                        $fullPath = $modulesRoot . '/' . $moduleName . '/' . $modulePath;
                        if (is_dir($fullPath)) {
                            $dirs[] = [
                                'path' => $fullPath,
                                'namespace' => $structureResolver->getModuleNamespace($moduleName, 'http'),
                            ];
                        }
                    }
                } catch (\InvalidArgumentException) {
                    continue;
                }
            }
        }

        return $dirs;
    }

    private static function loadControllersWithCache(ControllerLoader $loader): array
    {
        $cacheFile = self::ROUTE_CACHE_FILE;
        $cacheFileExists = FileExistenceCache::exists($cacheFile);
        if ($cacheFileExists) {
            $data = require $cacheFile;
            if (is_array($data) && isset($data['controllers']) && is_array($data['controllers'])) {
                $controllers = $data['controllers'];
                if (!self::hasControllerFilesChanged($controllers)) {
                    $routeData = $data['routeData'] ?? null;

                    if (is_array($routeData) && !array_is_list($routeData)) {
                        $host = $_SERVER['HTTP_HOST'] ?? '';
                        $routeData = $routeData[$host] ?? null;
                    }

                    return [
                        'controllers' => $controllers,
                        'routeData' => $routeData,
                    ];
                }
            }
        }

        $controllers = $loader->registerControllers();

        return [
            'controllers' => $controllers,
            'routeData' => null, // signal to build routes via reflection
        ];
    }

    private static function hasControllerFilesChanged(array $controllers): bool
    {
        $filesToCheck = [];

        foreach ($controllers as $meta) {
            if (!is_array($meta)) {
                return true;
            }

            $file = $meta['file'] ?? null;
            $mtime = $meta['mtime'] ?? null;

            if (!$file || $mtime === null) {
                return true;
            }

            $absoluteFile = self::toAbsolutePath($file);
            $filesToCheck[$absoluteFile] = $mtime;
        }

        if (empty($filesToCheck)) {
            return false;
        }

        // Use optimized batch file checking
        return FileExistenceCache::hasFilesChanged($filesToCheck);
    }

    private static function writeControllerCache(array $controllers, array $routeData = []): void
    {
        $cacheFile = self::ROUTE_CACHE_FILE;
        $dir = dirname($cacheFile);
        if (!FileExistenceCache::isDir($dir)) {
            mkdir($dir, 0777, true);
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';

        $existing = [];
        if (is_file($cacheFile)) {
            $existing = require $cacheFile;
            if (!is_array($existing)) {
                $existing = [];
            }
        }

        $cachedRouteData = $existing['routeData'] ?? [];
        if (!is_array($cachedRouteData) || array_is_list($cachedRouteData)) {
            $cachedRouteData = [];
        }

        $cachedRouteData[$host] = $routeData;

        $relativeControllers = array_map(function (array $meta) {
            if (isset($meta['file'])) {
                $meta['file'] = self::toRelativePath($meta['file']);
            }
            return $meta;
        }, $controllers);

        $export = var_export([
            'controllers' => $relativeControllers,
            'routeData' => $cachedRouteData,
        ], true);
        $content = '<?php return ' . $export . ';';

        $tmp = tempnam($dir, 'routes_');
        if ($tmp === false) {
            file_put_contents($cacheFile, $content);
            return;
        }

        file_put_contents($tmp, $content);
        @chmod($tmp, 0664);
        rename($tmp, $cacheFile);
    }

    private static function toRelativePath(string $path): string
    {
        if (str_starts_with($path, BASE_PATH . '/')) {
            return substr($path, strlen(BASE_PATH) + 1);
        }
        return $path;
    }

    private static function toAbsolutePath(string $path): string
    {
        if (!str_starts_with($path, '/')) {
            return BASE_PATH . '/' . $path;
        }
        return $path;
    }
}
