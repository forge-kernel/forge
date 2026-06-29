<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Warmers;

use Forge\Core\Bootstrap\OptimizedDirectoryScanner;
use Forge\Core\Config\Config;
use Forge\Core\Contracts\Cache\CacheWarmerInterface;
use Forge\Core\DI\Attributes\Injectable;
use Forge\Core\DI\Container;
use Forge\Core\Structure\StructureResolver;
use App\Modules\ForgeRouter\Routing\ControllerLoader;

#[Injectable]
final class ControllerMapCacheWarmer implements CacheWarmerInterface
{
    private const ROUTE_CACHE_FILE = BASE_PATH . '/storage/framework/cache/controller-map.php';

    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function warmCache(): void
    {
        $structureResolver = $this->container->has(StructureResolver::class)
            ? $this->container->get(StructureResolver::class)
            : null;

        $config = $this->container->has(Config::class)
            ? $this->container->get(Config::class)
            : null;

        $controllerDirs = $this->resolveControllerDirs($structureResolver, $config);

        $loader = new ControllerLoader($this->container, $controllerDirs, $structureResolver);
        $controllers = $loader->registerControllers();

        $this->writeCache($controllers);
    }

    private function resolveControllerDirs(?StructureResolver $structureResolver, ?Config $config): array
    {
        if ($structureResolver) {
            $appControllersPath = $structureResolver->getAppPath('controllers');
            $controllerDirs = [BASE_PATH . '/' . $appControllersPath];
        } else {
            $controllerDirs = [BASE_PATH . '/app/Controllers'];
        }

        $moduleControllerDirs = OptimizedDirectoryScanner::getControllerDirectories($config);

        return array_merge($controllerDirs, $moduleControllerDirs);
    }

    private function writeCache(array $controllers): void
    {
        $dir = dirname(self::ROUTE_CACHE_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $export = var_export([
            'controllers' => $controllers,
            'routeData' => [],
        ], true);

        $tmp = tempnam($dir, 'ctrl_');
        if ($tmp !== false) {
            file_put_contents($tmp, '<?php return ' . $export . ';');
            @chmod($tmp, 0664);
            rename($tmp, self::ROUTE_CACHE_FILE);
        } else {
            file_put_contents(self::ROUTE_CACHE_FILE, '<?php return ' . $export . ';');
        }
    }
}
