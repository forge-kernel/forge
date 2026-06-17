<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Module\Attributes\HubItem;
use Forge\Core\Module\ModuleLoader\Loader;
use ReflectionClass;
use ReflectionException;

#[Service]
final class HubItemRegistry
{
    private const string CLASS_MAP_FILE = BASE_PATH . '/modules/ForgeHub/config/hub_items.php';

    private static ?array $cachedClassMap = null;
    private static ?int $classMapMtime = null;
    private static ?array $cachedHubItems = null;
    private static ?int $hubItemsCacheMtime = null;

    public function __construct(
        private readonly Loader $loader
    )
    {
    }

    public function refresh(): void
    {
        $currentRegistry = $this->loader->getSortedModuleRegistry();
        $existingMap = $this->loadClassMap();
        $newMap = [];
        $hasChanges = false;

        foreach ($currentRegistry as $moduleInfo) {
            $moduleClassName = $moduleInfo['name'];
            $modulePath = $moduleInfo['path'] ?? null;

            if (!$modulePath || !is_dir($modulePath)) {
                continue;
            }

            $moduleFile = $this->findModuleClassFile($moduleClassName, $modulePath);
            if (!$moduleFile || !FileExistenceCache::exists($moduleFile)) {
                continue;
            }

            $currentMtime = filemtime($moduleFile);
            $existingEntry = $existingMap[$moduleClassName] ?? null;
            $existingMtime = $existingEntry['mtime'] ?? 0;

            if ($existingEntry && $currentMtime === $existingMtime && $existingEntry['modulePath'] === $modulePath) {
                $newMap[$moduleClassName] = $existingEntry;
                continue;
            }

            $hubItems = $this->scanModuleForHubItems($moduleClassName);
            $newMap[$moduleClassName] = [
                'hubItems' => $hubItems,
                'mtime' => $currentMtime,
                'modulePath' => $modulePath,
            ];
            $hasChanges = true;
        }

        foreach (array_keys($existingMap) as $existingClassName) {
            if (!isset($newMap[$existingClassName])) {
                $hasChanges = true;
            }
        }

        if ($hasChanges || !FileExistenceCache::exists(self::CLASS_MAP_FILE)) {
            $this->saveClassMap($newMap);
            $this->clearCache();
        }
    }

    private function loadClassMap(): array
    {
        if (!FileExistenceCache::exists(self::CLASS_MAP_FILE)) {
            return [];
        }

        $currentMtime = filemtime(self::CLASS_MAP_FILE);

        if (self::$cachedClassMap !== null && self::$classMapMtime === $currentMtime) {
            return self::$cachedClassMap;
        }

        $map = include self::CLASS_MAP_FILE;
        $result = is_array($map) ? $map : [];

        self::$cachedClassMap = $result;
        self::$classMapMtime = $currentMtime;

        return $result;
    }

    private function findModuleClassFile(string $className, string $modulePath): ?string
    {
        try {
            $reflection = new ReflectionClass($className);
            return $reflection->getFileName() ?: null;
        } catch (ReflectionException) {
            return null;
        }
    }

    private function scanModuleForHubItems(string $moduleClassName): array
    {
        try {
            $reflection = new ReflectionClass($moduleClassName);
            $attributes = $reflection->getAttributes(HubItem::class);
            $hubItems = [];

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                $hubItems[] = [
                    'label' => $instance->label,
                    'route' => $instance->route,
                    'icon' => $instance->icon?->value ?? null,
                    'order' => $instance->order,
                    'permissions' => array_map(fn($perm) => $perm->value, $instance->permissions ?? []),
                ];
            }

            return $hubItems;
        } catch (ReflectionException) {
            return [];
        }
    }

    private function saveClassMap(array $map): void
    {
        $dir = dirname(self::CLASS_MAP_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = "<?php\n\nreturn " . var_export($map, true) . ";\n";
        file_put_contents(self::CLASS_MAP_FILE, $content, LOCK_EX);
    }

    public function clearCache(): void
    {
        self::$cachedClassMap = null;
        self::$classMapMtime = null;
        self::$cachedHubItems = null;
        self::$hubItemsCacheMtime = null;
    }

    public function getHubItems(): array
    {
        $currentMtime = FileExistenceCache::exists(self::CLASS_MAP_FILE) ? filemtime(self::CLASS_MAP_FILE) : 0;

        if (self::$cachedHubItems !== null && self::$hubItemsCacheMtime === $currentMtime) {
            return self::$cachedHubItems;
        }

        $classMap = $this->loadClassMap();
        $currentRegistry = $this->loader->getSortedModuleRegistry();
        $registryClassNames = array_flip(array_column($currentRegistry, 'name'));
        $allHubItems = [];

        foreach ($classMap as $moduleClassName => $entry) {
            if (!isset($registryClassNames[$moduleClassName])) {
                continue;
            }

            foreach ($entry['hubItems'] as $hubItem) {
                $allHubItems[] = [
                    'label' => $hubItem['label'],
                    'route' => $hubItem['route'],
                    'icon' => $hubItem['icon'],
                    'order' => $hubItem['order'],
                    'permissions' => $hubItem['permissions'],
                    'module' => $moduleClassName,
                ];
            }
        }

        usort($allHubItems, fn($a, $b) => $a['order'] <=> $b['order']);

        self::$cachedHubItems = $allHubItems;
        self::$hubItemsCacheMtime = $currentMtime;

        return $allHubItems;
    }

    public function getHubItemsForModule(string $moduleClassName): array
    {
        $classMap = $this->loadClassMap();
        return $classMap[$moduleClassName]['hubItems'] ?? [];
    }
}
