<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Middleware;

use Forge\Core\Bootstrap\OptimizedDirectoryScanner;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Helpers\ModuleHelper;
use Forge\Core\Services\AttributeDiscoveryService;
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
     * Discovers middlewares via attribute scanning, then scans conventional
     * middleware directories. Returns both the middleware map and the list of
     * files/dirs scanned (for cache generation).
     *
     * @return array{middlewareMap: array, files: string[], dirs: string[]}
     */
    private function scanDirectories(): array
    {
        $autoMiddlewares = [];
        $discoveredClasses = [];
        $files = [];
        $dirs = [];

        // 1. Attribute-based discovery: classes with #[RegisterMiddleware] or #[Middleware]
        $basePaths = $this->getAttributeDiscoveryBasePaths();
        if (!empty($basePaths)) {
            $discoveryService = new AttributeDiscoveryService();
            $classMap = $discoveryService->discover($basePaths, [RegisterMiddleware::class, Middleware::class]);

            foreach ($classMap as $className => $metadata) {
                if (!in_array(RegisterMiddleware::class, $metadata['attributes'] ?? [], true) && !in_array(Middleware::class, $metadata['attributes'] ?? [], true)) {
                    continue;
                }

                if (!class_exists($className)) {
                    continue;
                }

                try {
                    $reflection = new ReflectionClass($className);
                    $attribute = $reflection->getAttributes(Middleware::class)[0] ?? $reflection->getAttributes(RegisterMiddleware::class)[0] ?? null;

                    if (!$attribute) {
                        continue;
                    }

                    /** @var RegisterMiddleware|Middleware $attrInstance */
                    $attrInstance = $attribute->newInstance();

                    if (!$attrInstance->enabled) {
                        continue;
                    }

                    if (!isset($autoMiddlewares[$attrInstance->group])) {
                        $autoMiddlewares[$attrInstance->group] = [];
                    }

                    $autoMiddlewares[$attrInstance->group][] = [
                        'class' => $className,
                        'order' => $attrInstance->order,
                        'overrideClass' => $attrInstance->overrideClass ?? $className,
                    ];
                    $discoveredClasses[$className] = true;
                    $files[] = $metadata['file'];
                } catch (ReflectionException $e) {
                    //
                }
            }
        }

        // 2. Conventional middleware directories: app/Middlewares/ and modules/{Module}/src/Middlewares/
        $middlewareDirs = $this->resolveMiddlewareDirectories();
        foreach ($middlewareDirs as $dir) {
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

                    try {
                        $className = $this->fileToClass($filePath);

                        if (isset($discoveredClasses[$className])) {
                            continue;
                        }

                        if ($className && class_exists($className)) {
                            $reflection = new ReflectionClass($className);
                            $attribute = $reflection->getAttributes(Middleware::class)[0] ?? $reflection->getAttributes(RegisterMiddleware::class)[0] ?? null;

                            if ($attribute) {
                                /** @var RegisterMiddleware|Middleware $attrInstance */
                                $attrInstance = $attribute->newInstance();

                                if (!$attrInstance->enabled) {
                                    continue;
                                }

                                if (!isset($autoMiddlewares[$attrInstance->group])) {
                                    $autoMiddlewares[$attrInstance->group] = [];
                                }

                                $autoMiddlewares[$attrInstance->group][] = [
                                    'class' => $className,
                                    'order' => $attrInstance->order,
                                    'overrideClass' => $attrInstance->overrideClass ?? $className,
                                ];
                            }
                        }
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
     * @return array<string>
     */
    private function getAttributeDiscoveryBasePaths(): array
    {
        return OptimizedDirectoryScanner::getAttributeDiscoveryPaths();
    }

    /**
     * @return array<string>
     */
    private function resolveMiddlewareDirectories(): array
    {
        $dirs = [];

        $appDir = $this->resolveAppMiddlewaresPath();
        if ($appDir !== null) {
            $dirs[] = $appDir;
        }

        $modulesDir = BASE_PATH . '/modules';
        if (is_dir($modulesDir)) {
            foreach (scandir($modulesDir) as $moduleName) {
                if ($moduleName === '.' || $moduleName === '..' || ModuleHelper::isModuleDisabled($moduleName)) {
                    continue;
                }

                $moduleDir = $modulesDir . '/' . $moduleName;
                if (!is_dir($moduleDir)) {
                    continue;
                }

                $middlewareDir = $this->resolveModuleMiddlewaresPath($moduleName);
                if ($middlewareDir !== null && is_dir($middlewareDir)) {
                    $dirs[] = $middlewareDir;
                }
            }
        }

        return $dirs;
    }

    private function resolveAppMiddlewaresPath(): ?string
    {
        if ($this->structureResolver) {
            try {
                $path = $this->structureResolver->getAppPath('middlewares');
                $fullPath = BASE_PATH . '/' . $path;
                return is_dir($fullPath) ? $fullPath : null;
            } catch (\InvalidArgumentException $e) {
                return $this->getDefaultAppMiddlewaresPath();
            }
        }

        return $this->getDefaultAppMiddlewaresPath();
    }

    private function getDefaultAppMiddlewaresPath(): ?string
    {
        $fullPath = BASE_PATH . '/app/Middlewares';
        return is_dir($fullPath) ? $fullPath : null;
    }

    private function resolveModuleMiddlewaresPath(string $moduleName): ?string
    {
        if ($this->structureResolver) {
            try {
                $path = $this->structureResolver->getModulePath($moduleName, 'middlewares');
                $fullPath = BASE_PATH . '/modules/' . $moduleName . '/' . $path;
                return is_dir($fullPath) ? $fullPath : null;
            } catch (\InvalidArgumentException $e) {
                return $this->getDefaultModuleMiddlewaresPath($moduleName);
            }
        }

        return $this->getDefaultModuleMiddlewaresPath($moduleName);
    }

    private function getDefaultModuleMiddlewaresPath(string $moduleName): ?string
    {
        $fullPath = BASE_PATH . '/modules/' . $moduleName . '/src/Middlewares';
        return is_dir($fullPath) ? $fullPath : null;
    }

    /**
     * Loads the middleware map from cache.
     * Always uses cache if valid, regardless of environment.
     * Validates cache by checking file modification times.
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
     * Check if the cached middleware map is still valid.
     * Uses directory mtimes as the primary signal — any file add/remove/modify
     * within a directory changes its mtime on most filesystems. Also verifies
     * that every previously-cached file still exists.
     *
     * @param array{files: array<string, int>, dirs: array<string, int>, mtime: int} $metadata Cache metadata with file list and mtimes
     * @return bool True if cache is valid, false if files have changed
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

        // Check that every previously-cached file still exists
        foreach ($cachedFiles as $file => $fileMtime) {
            if (!FileExistenceCache::exists($file)) {
                return false;
            }
        }

        // Directory mtime tracks structural changes (additions, deletions)
        foreach ($cachedDirs as $dir => $dirMtime) {
            $currentMtime = FileExistenceCache::getMtime($dir);
            if ($currentMtime === null || $currentMtime > $dirMtime) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if legacy cache (without metadata) is still valid.
     * This is a fallback for existing caches.
     *
     * @return bool True if cache appears valid, false otherwise
     */
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

        $dirsToCheck = $this->resolveMiddlewareDirectories();

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
     * Generates and caches the middleware map to a file.
     * Includes metadata (file list and modification times) for cache validation.
     *
     * @param array<string, array<int, array{class: class-string, overrideClass: ?string}>> $middlewareMap
     * @param string[] $files File paths that were scanned
     * @param string[] $dirs Directory paths that were scanned
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


    private function fileToClass(string $filepath): string
    {
        $basePath = BASE_PATH;
        $relativePath = str_replace($basePath, "", $filepath);
        $class = str_replace([".php", "/"], ["", "\\"], $relativePath);

        $class = ltrim($class, "\\");
        if (str_starts_with($class, "app\\")) {
            $class = str_replace("app\\", "App\\", $class);
        }

        if (preg_match('#modules/([^/]+)/src/Middlewares/(.*)\.php#', $relativePath, $matches)) {
            return "Modules\\{$matches[1]}\\Middlewares\\{$matches[2]}";
        }

        if (preg_match('#modules/([^/]+)/(.*)/Middlewares/(.*)\.php#', $relativePath, $matches)) {
            $namespacePath = str_replace('/', '\\', $matches[2]);
            return "Modules\\{$matches[1]}\\{$namespacePath}\\Middlewares\\{$matches[3]}";
        }

        return $class;
    }
}
