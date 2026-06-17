<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Services;

use Forge\Core\Cache\CacheManager;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Config\Environment;

#[Service]
final class EnhancedCacheService
{
    /** @var array<string, string> Cache tag descriptions */
    private array $cacheTags = [
        'commands' => 'CLI Commands',
        'controllers' => 'Controllers',
        'services' => 'Service Discovery',
        'attributes' => 'Attribute Discovery',
        'routes' => 'Routes & Middleware',
        'modules' => 'Module Registry',
        'autoloader' => 'Autoloader Class Map',
        'compiled_hooks' => 'Compiled Hooks',
        'views' => 'Compiled Views',
        'config' => 'Configuration Cache',
        'reflections' => 'Reflection Cache',
        'database' => 'Database Query Cache',
        'templates' => 'Template Cache',
        'sessions' => 'Session Data',
        'static_files' => 'Static Assets',
    ];

    public function __construct(
        private readonly CacheManager $cacheManager
    ) {
    }

    /**
     * Get comprehensive cache statistics
     */
    public function getDetailedStats(): array
    {
        $cacheFiles = $this->getAllCacheFiles();
        $totalSize = 0;
        
        foreach ($cacheFiles as $file) {
            $totalSize += $file['size'] ?? 0;
        }

        return [
            'total_files' => count($cacheFiles),
            'total_size' => $this->formatBytes($totalSize),
            'file_count' => count($cacheFiles),
            'tags' => $this->cacheTags,
            'files' => $cacheFiles,
        ];
    }

    /**
     * Get all cache files with metadata
     */
    public function getAllCacheFiles(): array
    {
        $cacheDir = BASE_PATH . '/storage/framework/cache';
        $files = [];

        if (!FileExistenceCache::isDir($cacheDir)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                $filePath = $fileInfo->getPathname();
                $relativePath = str_replace(BASE_PATH . '/', '', $filePath);
                
                $files[] = [
                    'path' => $relativePath,
                    'full_path' => $filePath,
                    'size' => $fileInfo->getSize(),
                    'modified' => date('Y-m-d H:i:s', $fileInfo->getMTime()),
                    'size_formatted' => $this->formatBytes($fileInfo->getSize()),
                    'tag' => $this->determineCacheTag($relativePath),
                ];
            }
        }

        return $files;
    }

    /**
     * Determine cache tag based on filename
     */
    private function determineCacheTag(string $filename): string
    {
        foreach ($this->cacheTags as $tag => $description) {
            $keywords = $this->getTagKeywords($tag);
            foreach ($keywords as $keyword) {
                if (str_contains((string)$filename, (string)$keyword)) {
                    return $tag;
                }
            }
        }
        return 'other';
    }

    /**
     * Get keywords that identify a cache tag
     */
    private function getTagKeywords(string $tag): array
    {
        $allKeywords = [
            'commands' => ['command', 'cli'],
            'controllers' => ['controller', 'route'],
            'services' => ['service', 'discovery', 'attribute'],
            'attributes' => ['attribute', 'reflection'],
            'routes' => ['route', 'middleware', 'router'],
            'modules' => ['module', 'registry'],
            'autoloader' => ['class_map', 'autoloader'],
            'compiled_hooks' => ['hook', 'compiled'],
            'views' => ['view', 'template'],
            'config' => ['config', 'app'],
            'reflections' => ['reflection', 'cache'],
            'database' => ['database', 'db', 'query'],
            'templates' => ['template', 'blade', 'twig'],
            'sessions' => ['session', 'auth'],
            'static_files' => ['asset', 'static', 'public']
        ];

        return $allKeywords[$tag] ?? [];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = 1024;

        for ($i = 0; $bytes >= $factor && $i < count($units) - 1; $i++) {
            $bytes /= $factor;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Clear cache by tag
     */
    public function clearByTag(string $tag): void
    {
        $cacheFiles = $this->getAllCacheFiles();
        $cleared = 0;

        foreach ($cacheFiles as $file) {
            if ($file['tag'] === $tag) {
                if (FileExistenceCache::exists($file['full_path'])) {
                    unlink($file['full_path']);
                    $cleared++;
                }
            }
        }

        $this->logOperation("Cleared tag '{$tag}': {$cleared} files");
    }

    /**
     * Clear all cache
     */
    public function clearAll(): void
    {
        $cacheFiles = $this->getAllCacheFiles();
        $cleared = 0;

        foreach ($cacheFiles as $file) {
            if (FileExistenceCache::exists($file['full_path'])) {
                unlink($file['full_path']);
                $cleared++;
            }
        }

        $this->logOperation("Cleared all cache: {$cleared} files");
    }

    /**
     * Clear expired cache files (older than TTL)
     */
    public function clearExpired(int $ttlHours = 24): void
    {
        $cacheFiles = $this->getAllCacheFiles();
        $cutoff = time() - ($ttlHours * 3600);
        $cleared = 0;

        foreach ($cacheFiles as $file) {
            if ($fileInfo = new \SplFileInfo($file['full_path'])) {
                if ($fileInfo->getMTime() < $cutoff) {
                    unlink($file['full_path']);
                    $cleared++;
                }
            }
        }

        $this->logOperation("Cleared expired cache: {$cleared} files");
    }

    /**
     * Get available tags with descriptions
     */
    public function getAvailableTags(): array
    {
        return $this->cacheTags;
    }

    /**
     * Log cache operations
     */
    private function logOperation(string $message): void
    {
        $env = Environment::getInstance();
        $logFile = BASE_PATH . '/storage/logs/cache_operations.log';
        
        $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}