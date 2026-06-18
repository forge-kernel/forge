<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Bootstrap;

use Forge\Core\Bootstrap\OptimizedDirectoryScanner;
use Forge\Core\Config\Config;
use Forge\Core\Debug\Metrics;
use Forge\Core\DI\Container;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Helpers\ModuleHelper;
use App\Modules\ForgeRouter\Middleware\EngineMiddlewareRegistry;
use App\Modules\ForgeRouter\Middleware\MiddlewareLoader;
use App\Modules\ForgeRouter\Routing\ControllerLoader;
use App\Modules\ForgeRouter\Routing\Router;
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
            : null;

        if ($structureResolver) {
            $appControllersPath = $structureResolver->getAppPath('controllers');
            $controllerDirs = [BASE_PATH . '/' . $appControllersPath];
        } else {
            $controllerDirs = [BASE_PATH . "/app/Controllers"];
        }

        $config = $container->has(Config::class) ? $container->get(Config::class) : null;
        $moduleControllerDirs = OptimizedDirectoryScanner::getControllerDirectories($config);

        // Merge with existing controller directories
        $controllerDirs = array_merge($controllerDirs, $moduleControllerDirs);
        Metrics::stop("router_controller_dir_scan");

        Metrics::start("router_controller_cache_load");
        $structureResolver = $container->has(StructureResolver::class)
            ? $container->get(StructureResolver::class)
            : null;
        $loader = new ControllerLoader($container, $controllerDirs, $structureResolver);
        $cacheResult = self::loadControllersWithCache($loader);
        $controllers = $cacheResult['controllers'];
        $cachedRouteData = $cacheResult['routeData'];
        Metrics::stop("router_controller_cache_load");

        Metrics::start("router_middleware_loader");
        /** @var MiddlewareLoader $middlewareLoader */
        $middlewareLoader = $container->make(MiddlewareLoader::class);
        $autoLoadedMap = $middlewareLoader->load();
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
            $configMiddlewareSet = array_flip($configMiddlewares);

            $hasExplicitEngineMiddlewares = false;
            foreach ($configMiddlewares as $mw) {
                if (EngineMiddlewareRegistry::isEngineMiddleware($mw)) {
                    $hasExplicitEngineMiddlewares = true;
                    break;
                }
            }

            if ($hasExplicitEngineMiddlewares) {
                foreach ($middlewareData as $item) {
                    if (is_string($item)) {
                        $item = ["class" => $item, "overrideClass" => null];
                    }

                    $autoClass = $item["class"] ?? null;
                    $overrideClass = $item["overrideClass"] ?? null;

                    if ($overrideClass && isset($configMiddlewareSet[$overrideClass])) {
                        unset($configMiddlewareSet[$overrideClass]);
                    }

                    if (!isset($configMiddlewareSet[$autoClass])) {
                        $configMiddlewareSet[$autoClass] = true;
                    }
                }

                $finalMiddlewareConfig[$group] = array_keys($configMiddlewareSet);
            } else {
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
        }

        Metrics::stop("router_middleware_merge");

        Metrics::start("router_init");
        $router = Router::init($container, $finalMiddlewareConfig);
        Metrics::stop("router_init");

        Metrics::start("router_register_controllers");
        if ($cachedRouteData !== null) {
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

    private static function loadControllersWithCache(ControllerLoader $loader): array
    {
        $cacheFile = self::ROUTE_CACHE_FILE;
        $cacheFileExists = FileExistenceCache::exists($cacheFile);
        if ($cacheFileExists) {
            $data = require $cacheFile;
            if (is_array($data) && isset($data['controllers']) && is_array($data['controllers'])) {
                $controllers = $data['controllers'];
                if (!self::hasControllerFilesChanged($controllers)) {
                    return [
                        'controllers' => $controllers,
                        'routeData' => array_key_exists('routeData', $data) ? $data['routeData'] : null,
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

            $filesToCheck[$file] = $mtime;
        }

        if (empty($filesToCheck)) {
            return false;
        }

        // Use optimized batch file checking
        return OptimizedDirectoryScanner::hasFilesChanged($filesToCheck);
    }

    private static function writeControllerCache(array $controllers, array $routeData = []): void
    {
        $cacheFile = self::ROUTE_CACHE_FILE;
        $dir = dirname($cacheFile);
        if (!FileExistenceCache::isDir($dir)) {
            mkdir($dir, 0777, true);
        }

        $export = var_export([
            'controllers' => $controllers,
            'routeData' => $routeData,
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
}
