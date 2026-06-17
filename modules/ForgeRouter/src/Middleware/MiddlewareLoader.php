<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Middleware;

use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Helpers\ModuleHelper;
use App\Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use ReflectionClass;
use ReflectionException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class MiddlewareLoader
{
  private const string CACHE_FILE = BASE_PATH . '/storage/framework/cache/middleware-map.php';

  private const array SCAN_DIRS = [
    BASE_PATH . '/app/Middlewares',
    BASE_PATH . '/modules/ForgeRouter/src/Http/Middlewares',
  ];

  /** @var array<string, array<int, array{class: class-string, order: int, overrideClass: ?string}>> */
  private array $loadedMiddlewares = [];

  public function __construct()
  {
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

    $autoMiddlewares = $this->scanDirectories();

    $finalMiddlewares = [];
    foreach ($autoMiddlewares as $group => $middlewares) {
      usort($middlewares, fn($a, $b) => $a['order'] <=> $b['order']);

      $finalMiddlewares[$group] = array_map(fn($m) => [
        'class' => $m['class'],
        'overrideClass' => $m['overrideClass'],
      ], $middlewares);
    }

    $this->generateCache($finalMiddlewares);

    return $finalMiddlewares;
  }

  /**
   * Scans defined directories for PHP classes with the RegisterMiddleware attribute.
   * @return array<string, array<int, array{class: class-string, order: int, overrideClass: ?string}>>
   */
  private function scanDirectories(): array
  {
    $autoMiddlewares = [];
    $filesToScan = [];

    foreach (self::SCAN_DIRS as $dir) {
      if (FileExistenceCache::isDir($dir)) {
        $directoryIterator = new RecursiveDirectoryIterator($dir);
        $iterator = new RecursiveIteratorIterator($directoryIterator);

        foreach ($iterator as $file) {
          if ($file->isFile() && $file->getExtension() === "php") {
            $filesToScan[] = $file->getPathname();
          }
        }
      }
    }

    $modulesDir = BASE_PATH . '/modules/';
    if (FileExistenceCache::isDir($modulesDir)) {
      foreach (glob("$modulesDir*/*/Middlewares/*.php") as $file) {
        $moduleName = ModuleHelper::extractModuleNameFromPath($file);
        if ($moduleName === null || !ModuleHelper::isModuleDisabled($moduleName)) {
          $filesToScan[] = $file;
        }
      }
      foreach (glob("$modulesDir*/*/Controllers/*/Middlewares/*.php") as $file) {
        $moduleName = ModuleHelper::extractModuleNameFromPath($file);
        if ($moduleName === null || !ModuleHelper::isModuleDisabled($moduleName)) {
          $filesToScan[] = $file;
        }
      }
    }

    foreach (array_unique($filesToScan) as $file) {
      try {
        $className = $this->fileToClass($file);

        if ($className && class_exists($className)) {
          $reflection = new ReflectionClass($className);
          $attribute = $reflection->getAttributes(RegisterMiddleware::class)[0] ?? null;

          if ($attribute) {
            /** @var RegisterMiddleware $attrInstance */
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

    return $autoMiddlewares;
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
   * Check if the cached middleware map is still valid by comparing file modification times.
   *
   * @param array{files: array<string, int>, dirs: array<string, int>, mtime: int} $metadata Cache metadata with file list and mtimes
   * @return bool True if cache is valid, false if files have changed
   */
  private function isCacheValid(array $metadata): bool
  {
    $cachedFiles = $metadata['files'] ?? [];
    $cachedDirs = $metadata['dirs'] ?? [];

    if (!empty($cachedFiles)) {
      $filePaths = array_keys($cachedFiles);
      FileExistenceCache::preload($filePaths);
    }

    if (!empty($cachedDirs)) {
      $dirPaths = array_keys($cachedDirs);
      FileExistenceCache::preload($dirPaths);
    }

    foreach ($cachedFiles as $file => $fileMtime) {
      $currentMtime = FileExistenceCache::getMtime($file);
      if ($currentMtime === null || $currentMtime > $fileMtime) {
        return false;
      }
    }

    foreach ($cachedDirs as $dir => $dirMtime) {
      $currentMtime = FileExistenceCache::getMtime($dir);
      if ($currentMtime === null || $currentMtime > $dirMtime) {
        return false;
      }
    }

    // Since we track directory modification times, we don't need to recursively scan all directories 
    // to find newly created or deleted files! Any addition/deletion changes the directory mtime.
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

    $dirsToCheck = [];
    foreach (self::SCAN_DIRS as $dir) {
      $dirsToCheck[] = $dir;
    }

    if (!empty($dirsToCheck)) {
      FileExistenceCache::preload($dirsToCheck);
    }

    foreach (self::SCAN_DIRS as $dir) {
      if (FileExistenceCache::isDir($dir)) {
        $dirMtime = FileExistenceCache::getMtime($dir);
        if ($dirMtime !== null && $dirMtime > $cacheMtime) {
          return false;
        }
      }
    }

    return true;
  }

  private function getAllMiddlewareFilesAndDirs(): array
  {
    $files = [];
    $dirs = [];

    foreach (self::SCAN_DIRS as $dir) {
      if (FileExistenceCache::isDir($dir)) {
        $dirs[] = $dir;
        $directoryIterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
          if ($file->isDir()) {
            $dirs[] = $file->getPathname();
          } elseif ($file->isFile() && $file->getExtension() === "php") {
            $files[] = $file->getPathname();
          }
        }
      }
    }

    $modulesDir = BASE_PATH . '/modules/';
    if (FileExistenceCache::isDir($modulesDir)) {
      $dirs[] = $modulesDir;
      foreach (glob("$modulesDir*/*/Middlewares", GLOB_ONLYDIR) as $dir) {
        $dirs[] = $dir;
      }
      foreach (glob("$modulesDir*/*/Middlewares/*.php") as $file) {
        $moduleName = ModuleHelper::extractModuleNameFromPath($file);
        if ($moduleName === null || !ModuleHelper::isModuleDisabled($moduleName)) {
          $files[] = $file;
        }
      }

      foreach (glob("$modulesDir*/*/Controllers/*/Middlewares", GLOB_ONLYDIR) as $dir) {
        $dirs[] = $dir;
      }
      foreach (glob("$modulesDir*/*/Controllers/*/Middlewares/*.php") as $file) {
        $moduleName = ModuleHelper::extractModuleNameFromPath($file);
        if ($moduleName === null || !ModuleHelper::isModuleDisabled($moduleName)) {
          $files[] = $file;
        }
      }
    }

    return [
      'files' => array_unique($files),
      'dirs' => array_unique($dirs)
    ];
  }

  /**
   * Generates and caches the middleware map to a file.
   * Includes metadata (file list and modification times) for cache validation.
   *
   * @param array<string, array<int, array{class: class-string, overrideClass: ?string}>> $middlewareMap
   */
  private function generateCache(array $middlewareMap): void
  {
    if (!FileExistenceCache::isDir(dirname(self::CACHE_FILE))) {
      mkdir(dirname(self::CACHE_FILE), 0777, true);
    }

    $systemFiles = $this->getAllMiddlewareFilesAndDirs();
    $files = $systemFiles['files'];
    $dirs = $systemFiles['dirs'];

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
    if (str_starts_with($class, "engine\\Core\\")) {
      $class = str_replace("engine\\Core\\", "Forge\\Core\\", $class);
    } elseif (str_starts_with($class, "app\\")) {
      $class = str_replace("app\\", "App\\", $class);
    }

    if (preg_match('#modules/([^/]+)/src/Middlewares/(.*)\.php#', $relativePath, $matches)) {
      return "App\\Modules\\{$matches[1]}\\Middlewares\\{$matches[2]}";
    }

    if (preg_match('#modules/ForgeRouter/src/Http/Middlewares/(.*)\.php#', $relativePath, $matches)) {
      return "App\\Modules\\ForgeRouter\\Http\\Middlewares\\{$matches[1]}";
    }

    return $class;
  }
}
