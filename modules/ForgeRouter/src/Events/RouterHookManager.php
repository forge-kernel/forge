<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Events;

use Forge\Core\DI\Container;
use Forge\Core\Helpers\FileExistenceCache;
use Throwable;

final class RouterHookManager
{
    private static array $hooks = [];
    private static bool $compiledLoaded = false;
    private static string $cacheFile;

    public static function init(): void
    {
        self::$cacheFile = BASE_PATH . '/storage/framework/cache/router_hooks.php';
    }

    public static function addHook(RouterHookName $hookName, callable|array $callback): void
    {
        $name = $hookName->value;

        if (!isset(self::$hooks[$name])) {
            self::$hooks[$name] = [];
        }

        foreach (self::$hooks[$name] as $registeredCallback) {
            if (
                is_array($registeredCallback) && is_array($callback) &&
                $registeredCallback[0] === $callback[0] && $registeredCallback[1] === $callback[1]
            ) {
                return;
            } elseif ($registeredCallback === $callback) {
                return;
            }
        }

        self::$hooks[$name][] = $callback;
    }

    public static function triggerHook(RouterHookName $hookName, ...$args): void
    {
        if (!self::$compiledLoaded) {
            self::loadCompiled();
            self::$compiledLoaded = true;
        }

        $name = $hookName->value;

        if (!isset(self::$hooks[$name])) {
            return;
        }

        $container = Container::getInstance();

        foreach (self::$hooks[$name] as $callback) {
            if (is_callable($callback)) {
                call_user_func_array($callback, $args);
            } elseif (is_array($callback) && count($callback) === 2 && method_exists($callback[0], $callback[1])) {
                $class = $callback[0];
                $method = $callback[1];
                $reflection = new \ReflectionMethod($class, $method);
                if ($reflection->isStatic()) {
                    call_user_func_array($callback, $args);
                } elseif ($container->has($class)) {
                    $instance = $container->get($class);
                    call_user_func_array([$instance, $method], $args);
                } else {
                    try {
                        $instance = $container->make($class);
                        call_user_func_array([$instance, $method], $args);
                    } catch (\Throwable $e) {
                        call_user_func_array($callback, $args);
                    }
                }
            }
        }
    }

    public static function discover(): void
    {
        if (self::$cacheFile === null) {
            self::init();
        }

        $cacheFile = self::$cacheFile;
        if (FileExistenceCache::exists($cacheFile)) {
            self::loadCompiled();

            // If the compiled cache is empty or failed to load, fall through
            // and rediscover hooks from the module registry.
            if (!empty(self::$hooks)) {
                self::$compiledLoaded = true;
                return;
            }
        }

        self::discoverFromModuleRegistry();
        self::compile();
        self::$compiledLoaded = true;
    }

    /**
     * Force a full rebuild of the router hook cache from the module registry.
     * Useful for cache:warm and manual cache invalidation.
     */
    public static function rebuild(): void
    {
        if (self::$cacheFile === null) {
            self::init();
        }

        self::debugReset();

        if (FileExistenceCache::exists(self::$cacheFile)) {
            @unlink(self::$cacheFile);
        }

        self::discoverFromModuleRegistry();
        self::compile();
        self::$compiledLoaded = true;
    }

    /**
     * Clear the compiled router hook cache without rebuilding it.
     * The next request will rediscover hooks automatically.
     */
    public static function clearCompiled(): void
    {
        if (self::$cacheFile === null) {
            self::init();
        }

        self::debugReset();

        if (FileExistenceCache::exists(self::$cacheFile)) {
            @unlink(self::$cacheFile);
        }
    }

    private static function discoverFromModuleRegistry(): void
    {
        try {
            $container = Container::getInstance();
            if (!$container->has(\Forge\Core\Module\ModuleLoader\Loader::class)) {
                return;
            }

            $moduleLoader = $container->get(\Forge\Core\Module\ModuleLoader\Loader::class);
            $modules = $moduleLoader->getSortedModuleRegistry();

            foreach ($modules as $moduleInfo) {
                $className = $moduleInfo['name'] ?? null;
                if (!$className || !class_exists($className)) {
                    continue;
                }

                try {
                    $reflection = new \ReflectionClass($className);
                    foreach ($reflection->getMethods() as $method) {
                        $attrs = $method->getAttributes(RouterHookAttribute::class);
                        foreach ($attrs as $attr) {
                            $instance = $attr->newInstance();
                            self::addHook($instance->hook, [$className, $method->getName()]);
                        }
                    }
                } catch (\ReflectionException $e) {
                    continue;
                }
            }
        } catch (Throwable $e) {
            error_log("Failed to discover router hooks: " . $e->getMessage());
        }
    }

    public static function compile(): void
    {
        if (self::$cacheFile === null) {
            self::init();
        }

        $dir = dirname(self::$cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $data = [];
        foreach (self::$hooks as $hookName => $callbacks) {
            $data[$hookName] = [];
            foreach ($callbacks as $callback) {
                if (is_array($callback) && is_string($callback[0]) && is_string($callback[1])) {
                    $data[$hookName][] = [
                        'type' => 'method',
                        'class' => $callback[0],
                        'method' => $callback[1],
                    ];
                }
            }
        }

        $content = '<?php return ' . var_export($data, true) . ';';
        file_put_contents(self::$cacheFile, $content);
    }

    private static function loadCompiled(): void
    {
        if (self::$cacheFile === null) {
            self::init();
        }

        $cacheFile = self::$cacheFile;
        if (!FileExistenceCache::exists($cacheFile)) {
            return;
        }

        $compiled = include $cacheFile;
        if (!is_array($compiled)) {
            return;
        }

        foreach ($compiled as $hookName => $hooks) {
            foreach ($hooks as $hook) {
                if ($hook['type'] === 'method') {
                    try {
                        if (!method_exists($hook['class'], $hook['method'])) {
                            continue;
                        }
                        $hookNameEnum = RouterHookName::from($hookName);
                        $callback = [$hook['class'], $hook['method']];
                        self::addHook($hookNameEnum, $callback);
                    } catch (Throwable $e) {
                        error_log("Failed to load compiled router hook: " . $e->getMessage());
                    }
                }
            }
        }
    }

    public static function debugReset(): void
    {
        self::$hooks = [];
        self::$compiledLoaded = false;
    }

    public static function debugGetHooks(): array
    {
        return self::$hooks;
    }
}
