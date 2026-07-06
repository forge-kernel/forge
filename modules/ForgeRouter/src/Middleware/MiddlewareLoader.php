<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Middleware;

use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Helpers\ModuleHelper;
use Forge\Core\Structure\StructureResolver;
use Modules\ForgeRouter\Middleware\Attributes\Middleware;
use Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use ReflectionClass;
use ReflectionException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class MiddlewareLoader
{
    private const string CACHE_FILE = BASE_PATH . '/storage/framework/cache/middleware-map.php';

    /** @var array<string, array<int, array{class: class-string, order: int, overrideClass: ?string}>> */
    private array $loadedMiddlewares = [];

    public function __construct(
        private readonly ?StructureResolver $structureResolver = null,
    ) {
    }

    /**
     * Loads auto-registered middlewares from cache or by scanning.
     *
     * @return array<string, class-string[]>
     */
    public function load(): array
    {
        $cached = $this->loadCache();

        if ($cached) {
            return $cached;
        }

        $scanResult = $this->scanDirectories();
        $autoMiddlewares = $scanResult['middlewareMap'];

        $finalMiddlewares = [];
        foreach ($autoMiddlewares as $group => $middlewares) {
            usort($middlewares, fn($a, $b) => $a['order'] <=> $b['order']);

            $finalMiddlewares[$group] = array_map(fn($m) => [
                'class' => $m['class'],
                'overrideClass' => $m['overrideClass'],
            ], $middlewares);
        }

        $this->generateCache($finalMiddlewares, $scanResult['files'], $scanResult['dirs']);

        return $finalMiddlewares;
    }

    /**
     * Scans middleware directories defined by StructureResolver, reads
     * #[Middleware]/#[RegisterMiddleware] metadata, and builds the middleware map.
     * Classes without a middleware attribute are skipped.
     *
     * @return array{middlewareMap: array, files: string[], dirs: string[]}
     */
    private function scanDirectories(): array
    {
        $autoMiddlewares = [];
        $files = [];
        $dirs = [];

        $middlewareDirs = $this->resolveMiddlewareDirectories();

        foreach ($middlewareDirs as $dirInfo) {
            $dir = $dirInfo['path'];
            $namespace = $dirInfo['namespace'];

            if (!FileExistenceCache::isDir($dir)) {
                continue;
            }

            $dirs[] = $dir;

            $directoryIterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST);

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $dirs[] = $item->getPathname();
                } elseif ($item->isFile() && $item->getExtension() === "php") {
                    $filePath = $item->getPathname();
                    $files[] = $filePath;

                    $className = self::buildClassName($filePath, $dir, $namespace);

                    if (!$className || !class_exists($className)) {
                        continue;
                    }

                    try {
                        $reflection = new ReflectionClass($className);

                        $attribute = $reflection->getAttributes(Middleware::class)[0]
                            ?? $reflection->getAttributes(RegisterMiddleware::class)[0]
                            ?? null;

                        if (!$attribute) {
                            continue;
                        }

                        $attrInstance = $attribute->newInstance();

                        if (!$attrInstance->enabled) {
                            continue;
                        }

                        $group = $attrInstance->group;
                        $order = $attrInstance->order;
                        $overrideClass = $attrInstance->overrideClass;

                        if (!isset($autoMiddlewares[$group])) {
                            $autoMiddlewares[$group] = [];
                        }

                        $autoMiddlewares[$group][] = [
                            'class' => $className,
                            'order' => $order,
                            'overrideClass' => $overrideClass ?? $className,
                        ];
                    } catch (ReflectionException $e) {
                        //
                    }
                }
            }
        }

        return [
            'middlewareMap' => $autoMiddlewares,
            'files' => array_values(array_unique($files)),
            'dirs' => array_values(array_unique($dirs)),
        ];
    }

    /**
     * Builds the fully-qualified class name for a file within a scanned directory.
     */
    private static function buildClassName(string $filePath, string $baseDir, string $namespace): ?string
    {
        if (!str_starts_with($filePath, $baseDir)) {
            return null;
        }

        $relative = substr($filePath, strlen($baseDir) + 1);
        $relative = str_replace('.php', '', $relative);

        if ($relative === '' || $relative === false) {
            return null;
        }

        $parts = explode('/', $relative);
        $parts = array_map(fn(string $part) => str_replace(['-', '_'], '', $part), $parts);

        return $namespace . '\\' . implode('\\', $parts);
    }

    /**
     * @return array<int, array{path: string, namespace: string}>
     */
    private function resolveMiddlewareDirectories(): array
    {
        $directories = [];

        $appPaths = $this->structureResolver
            ? $this->structureResolver->getAppPaths('middlewares')
            : ['app/Middlewares', 'app/Http/Middlewares', 'app/Controllers/Middlewares'];

        foreach ($appPaths as $appPath) {
            $fullPath = BASE_PATH . '/' . $appPath;
            if (!is_dir($fullPath)) {
                continue;
            }

            $relative = str_replace('app/', '', $appPath);
            $directories[] = [
                'path' => $fullPath,
                'namespace' => 'App\\' . str_replace('/', '\\', $relative),
            ];
        }

        $modulesRoot = $this->structureResolver
            ? $this->structureResolver->getModulesRoot()
            : 'modules';
        $modulesPath = BASE_PATH . '/' . $modulesRoot;

        if (is_dir($modulesPath)) {
            foreach (scandir($modulesPath) as $moduleName) {
                if ($moduleName === '.' || $moduleName === '..') {
                    continue;
                }
                if (ModuleHelper::isModuleDisabled($moduleName)) {
                    continue;
                }

                $moduleDir = $modulesPath . '/' . $moduleName;
                if (!is_dir($moduleDir)) {
                    continue;
                }

                try {
                    $modulePaths = $this->structureResolver
                        ? $this->structureResolver->getModulePaths($moduleName, 'middlewares')
                        : ['src/Middlewares', 'src/Http/Middlewares', 'src/Controllers/Middlewares'];
                } catch (\InvalidArgumentException) {
                    continue;
                }

                foreach ($modulePaths as $modulePath) {
                    $fullPath = $moduleDir . '/' . $modulePath;
                    if (!is_dir($fullPath)) {
                        continue;
                    }

                    $relativeNs = str_replace('src/', '', $modulePath);
                    $directories[] = [
                        'path' => $fullPath,
                        'namespace' => 'Modules\\' . $moduleName . '\\' . str_replace('/', '\\', $relativeNs),
                    ];
                }
            }
        }

        return $directories;
    }

    /**
     * Loads the middleware map from cache.
     *
     * @return array<string, class-string[]>|null
     */
    private function loadCache(): ?array
    {
        if (!FileExistenceCache::exists(self::CACHE_FILE)) {
            return null;
        }

        try {
            $cachedData = include self::CACHE_FILE;
            if (is_array($cachedData) && isset($cachedData['_metadata'])) {
                $metadata = $cachedData['_metadata'];
                $middlewareMap = $cachedData['data'] ?? [];

                if ($this->isCacheValid($metadata)) {
                    $result = [];
                    foreach ($middlewareMap as $group => $data) {
                        $result[$group] = array_column($data, 'class');
                    }
                    return $result;
                }

                return null;
            }

            if (is_array($cachedData) && !isset($cachedData['_metadata'])) {
                if ($this->isLegacyCacheValid()) {
                    $result = [];
                    foreach ($cachedData as $group => $data) {
                        $result[$group] = array_column($data, 'class');
                    }
                    return $result;
                }
            }
        } catch (\Exception $e) {
            //
        }

        return null;
    }

    /**
     * @param array{files: array<string, int>, dirs: array<string, int>, mtime: int} $metadata
     */
    private function isCacheValid(array $metadata): bool
    {
        $cachedFiles = $metadata['files'] ?? [];
        $cachedDirs = $metadata['dirs'] ?? [];

        if (!empty($cachedFiles)) {
            FileExistenceCache::preload(array_keys($cachedFiles));
        }

        if (!empty($cachedDirs)) {
            FileExistenceCache::preload(array_keys($cachedDirs));
        }

        foreach ($cachedFiles as $file => $fileMtime) {
            if (!FileExistenceCache::exists($file)) {
                return false;
            }
        }

        foreach ($cachedDirs as $dir => $dirMtime) {
            $currentMtime = FileExistenceCache::getMtime($dir);
            if ($currentMtime === null || $currentMtime > $dirMtime) {
                return false;
            }
        }

        return true;
    }

    private function isLegacyCacheValid(): bool
    {
        $cacheMtime = @filemtime(self::CACHE_FILE);
        if ($cacheMtime === false) {
            return false;
        }

        $age = time() - $cacheMtime;
        if ($age < 1) {
            return true;
        }

        $dirsToCheck = [];
        foreach ($this->resolveMiddlewareDirectories() as $dirInfo) {
            $dirsToCheck[] = $dirInfo['path'];
        }

        if (!empty($dirsToCheck)) {
            FileExistenceCache::preload($dirsToCheck);
        }

        foreach ($dirsToCheck as $dir) {
            if (FileExistenceCache::isDir($dir)) {
                $dirMtime = FileExistenceCache::getMtime($dir);
                if ($dirMtime !== null && $dirMtime > $cacheMtime) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param array<string, array<int, array{class: class-string, overrideClass: ?string}>> $middlewareMap
     * @param string[] $files
     * @param string[] $dirs
     */
    private function generateCache(array $middlewareMap, array $files, array $dirs): void
    {
        if (!FileExistenceCache::isDir(dirname(self::CACHE_FILE))) {
            mkdir(dirname(self::CACHE_FILE), 0777, true);
        }

        $fileMetadata = [];
        $dirMetadata = [];
        $latestMtime = 0;

        foreach ($files as $file) {
            $fileMtime = @filemtime($file);
            if ($fileMtime !== false) {
                $fileMetadata[$file] = $fileMtime;
                $latestMtime = max($latestMtime, $fileMtime);
            }
        }

        foreach ($dirs as $dir) {
            $dirMtime = @filemtime($dir);
            if ($dirMtime !== false) {
                $dirMetadata[$dir] = $dirMtime;
                $latestMtime = max($latestMtime, $dirMtime);
            }
        }

        $cacheData = [
            'data' => $middlewareMap,
            '_metadata' => [
                'files' => $fileMetadata,
                'dirs' => $dirMetadata,
                'mtime' => $latestMtime,
            ],
        ];

        $cacheContent = "<?php return " . var_export($cacheData, true) . ";";
        file_put_contents(self::CACHE_FILE, $cacheContent);
    }
}
