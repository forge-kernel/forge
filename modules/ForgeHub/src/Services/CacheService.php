<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Services;

use Forge\Core\Cache\CacheManager;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Config\Environment;

#[Service]
final class CacheService
{
    public function __construct(
        private readonly CacheManager $cacheManager
    ) {
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        return [
            'driver' => $this->getDriverName(),
            'keys_count' => $this->getKeysCount(),
        ];
    }

    /**
     * Clear all cache
     */
    public function clearAll(): void
    {
        $this->cacheManager->clear();
    }

    /**
     * Clear cache by tag
     */
    public function clearTag(string $tag): void
    {
        $this->cacheManager->clearTag($tag);
    }

    /**
     * Clear expired cache entries
     */
    public function clearExpired(int $ttlHours = 24): void
    {
        try {
            $reflection = new \ReflectionClass($this->cacheManager);
            $driverProperty = $reflection->getProperty('driver');
            $driverProperty->setAccessible(true);
            $driver = $driverProperty->getValue($this->cacheManager);

            if ($driver && method_exists($driver, 'clearExpired')) {
                $driver->clearExpired($ttlHours * 3600); // Convert hours to seconds
            }
        } catch (\ReflectionException) {
        }
    }

    /**
     * Get available cache tags with descriptions
     */
    public function getAvailableTags(): array
    {
        return $this->getCacheTags();
    }

    private function getDriverName(): string
    {
        try {
            $reflection = new \ReflectionClass($this->cacheManager);
            $driverProperty = $reflection->getProperty('driver');
            $driverProperty->setAccessible(true);
            $driver = $driverProperty->getValue($this->cacheManager);
            return $driver ? get_class($driver) : 'Unknown';
        } catch (\ReflectionException) {
            return 'Unknown';
        }
    }

    private function getKeysCount(): int
    {
        try {
            $reflection = new \ReflectionClass($this->cacheManager);
            $driverProperty = $reflection->getProperty('driver');
            $driverProperty->setAccessible(true);
            $driver = $driverProperty->getValue($this->cacheManager);

            if ($driver && method_exists($driver, 'keys')) {
                $keys = $driver->keys();
                return is_array($keys) ? count($keys) : 0;
            }
        } catch (\ReflectionException) {
        }
        return 0;
    }

    /**
     * Get cache tags with descriptions
     */
    private function getCacheTags(): array
    {
        return [
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
    }
}