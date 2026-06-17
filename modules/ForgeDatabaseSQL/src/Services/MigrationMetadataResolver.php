<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Services;

use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Attributes\Migration as MigrationAttribute;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Structure\StructureResolver;
use Forge\Traits\StringHelper;
use ReflectionClass;
use ReflectionException;
use Throwable;

#[Service]
final class MigrationMetadataResolver
{
    use OutputHelper;

    private array $metadataCache = [];
    private array $reflectionCache = [];

    public function __construct(
        private readonly MigrationPathResolverService $pathResolver,
        private readonly ?StructureResolver $structureResolver = null
    ) {}

    /**
     * Extract metadata from migration path with optimized caching
     */
    public function extractMetadata(string $path): array
    {
        $cacheKey = md5($path);

        if (isset($this->metadataCache[$cacheKey])) {
            return $this->metadataCache[$cacheKey];
        }

        $metadata = $this->resolveMetadata($path);
        $this->metadataCache[$cacheKey] = $metadata;

        return $metadata;
    }

    /**
     * Extract metadata for multiple paths in bulk
     */
    public function extractMetadataBulk(array $paths): array
    {
        $results = [];
        $uncachedPaths = [];

        foreach ($paths as $path) {
            $cacheKey = md5($path);
            if (isset($this->metadataCache[$cacheKey])) {
                $results[$path] = $this->metadataCache[$cacheKey];
            } else {
                $uncachedPaths[] = $path;
            }
        }

        foreach ($uncachedPaths as $path) {
            $results[$path] = $this->extractMetadata($path);
        }

        return $results;
    }

    /**
     * Get migration type from path with minimal reflection
     */
    public function extractType(string $path): string
    {
        return $this->pathResolver->extractTypeFromPath($path);
    }

    /**
     * Get module name from path
     */
    public function extractModule(string $path): ?string
    {
        return $this->pathResolver->extractModuleFromPath($path);
    }

    /**
     * Get group name from path with cached reflection
     */
    public function extractGroup(string $path): ?string
    {
        $cacheKey = 'group_' . md5($path);
        
        if (isset($this->metadataCache[$cacheKey])) {
            return $this->metadataCache[$cacheKey];
        }

        $group = $this->resolveGroupFromPath($path);
        $this->metadataCache[$cacheKey] = $group;

        return $group;
    }

    /**
     * Get all metadata for a migration path
     */
    public function getCompleteMetadata(string $path): array
    {
        $type = $this->extractType($path);
        $module = $this->extractModule($path);
        $group = $this->extractGroup($path);

        return [
            'type' => $type,
            'module' => $module,
            'group' => $group,
            'path' => $path,
            'filename' => basename($path),
            'classname' => $this->pathResolver->getMigrationClassName($path)
        ];
    }

    /**
     * Clear metadata cache
     */
    public function clearCache(): void
    {
        $this->metadataCache = [];
        $this->reflectionCache = [];
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'metadata_cache_size' => count($this->metadataCache),
            'reflection_cache_size' => count($this->reflectionCache),
            'memory_usage' => memory_get_usage(true)
        ];
    }

    private function resolveMetadata(string $path): array
    {
        $type = $this->extractType($path);
        $module = $this->extractModule($path);
        $group = $this->extractGroup($path);

        return [$type, $module, $group];
    }

    private function resolveGroupFromPath(string $path): ?string
    {
        try {
            $className = $this->pathResolver->getMigrationClassName($path);
            
            if (!isset($this->reflectionCache[$className])) {
                require_once $path;
                
                if (!class_exists($className)) {
                    return null;
                }

                $reflection = new ReflectionClass($className);
                $this->reflectionCache[$className] = $reflection;
            }

            $reflection = $this->reflectionCache[$className];
            $attributes = $reflection->getAttributes(GroupMigration::class);

            if (!empty($attributes)) {
                $instance = $attributes[0]->newInstance();
                return $instance->name ?? null;
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Preload metadata for multiple paths
     */
    public function preloadMetadata(array $paths): void
    {
        foreach ($paths as $path) {
            $this->extractMetadata($path);
        }
    }

    /**
     * Validate migration class exists and is correct
     */
    public function validateMigration(string $path): bool
    {
        try {
            $className = $this->pathResolver->getMigrationClassName($path);
            
            if (!isset($this->reflectionCache[$className])) {
                require_once $path;
                
                if (!class_exists($className)) {
                    return false;
                }

                $reflection = new ReflectionClass($className);
                $this->reflectionCache[$className] = $reflection;
            }

            $reflection = $this->reflectionCache[$className];
            return $reflection->isInstantiable();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get migration attributes without full reflection
     */
    public function getMigrationAttributes(string $path): array
    {
        try {
            $className = $this->pathResolver->getMigrationClassName($path);
            
            if (!isset($this->reflectionCache[$className])) {
                require_once $path;
                
                if (!class_exists($className)) {
                    return [];
                }

                $reflection = new ReflectionClass($className);
                $this->reflectionCache[$className] = $reflection;
            }

            $reflection = $this->reflectionCache[$className];
            $attributes = [];

            foreach ($reflection->getAttributes() as $attribute) {
                $attributes[] = [
                    'name' => $attribute->getName(),
                    'arguments' => $attribute->getArguments()
                ];
            }

            return $attributes;
        } catch (Throwable $e) {
            return [];
        }
    }
}
