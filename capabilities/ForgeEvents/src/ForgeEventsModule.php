<?php

declare(strict_types=1);

namespace Capability\ForgeEvents;

use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Requires;
use Capability\ForgeEvents\Attributes\EventListener;
use Capability\ForgeEvents\Services\EventDispatcher;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Helpers\ModuleHelper;
use Forge\Core\Structure\StructureResolver;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use Forge\Core\Module\Traits\RegistersCommands;
use ReflectionMethod;

#[Module(
    name: "ForgeEvents",
    version: "1.4.18",
    description: "An Event Queue system by forge",
    order: 99,
    author: 'Forge Team',
    license: 'MIT',
    type: 'communication',
    tags: ['communication', 'event', 'queue', 'event-queue', 'event-dispatcher', 'event-listener']
)]
#[Compatibility(framework: ">=0.1.0", php: ">=8.3")]
#[Requires(module: "forge-database-sql")]
#[Repository(type: "git", url: "https://github.com/forge-kernel/kernel-module-registry")]
#[ConfigDefaults(defaults: [
    'forge_events' => [
        'queue_driver' => 'database',
        'queue_list' => ['default', 'cron', 'pipelines'],
        'scheduled_events' => [],
    ]
])]
final class ForgeEventsModule
{
    use RegistersCommands;

    public function register(Container $container): void
    {
        $this->setupConfigDefaults($container);
        $eventDispatcher = $container->has(EventDispatcher::class)
            ? $container->get(EventDispatcher::class)
            : null;

        if ($eventDispatcher) {
            $container->setInstance(EventDispatcher::class, $eventDispatcher);
            $container->setInstance(\Forge\Core\Contracts\EventDispatcherInterface::class, $eventDispatcher);
        }

        if (!$eventDispatcher) {
            return;
        }

        $structureResolver = $container->has(StructureResolver::class)
            ? $container->get(StructureResolver::class)
            : new StructureResolver();

        foreach (['events', 'listeners'] as $type) {
            foreach ($structureResolver->getAppPaths($type) as $path) {
                $fullPath = BASE_PATH . '/' . $path;
                if (is_dir($fullPath)) {
                    $this->scanDirectory(
                        $fullPath,
                        $structureResolver->getAppNamespace($type, $path),
                        $eventDispatcher,
                        $container
                    );
                }
            }
        }

        $modulesRoots = $structureResolver->getModulesRoots();
        foreach ($modulesRoots as $modulesRoot) {
            $modulesPath = BASE_PATH . '/' . $modulesRoot;
            if (!is_dir($modulesPath)) {
                continue;
            }
            foreach (scandir($modulesPath) as $moduleName) {
                if ($moduleName === '.' || $moduleName === '..') {
                    continue;
                }
                if (ModuleHelper::isModuleDisabled($moduleName)) {
                    continue;
                }

                try {
                    foreach (['events', 'listeners'] as $type) {
                        foreach ($structureResolver->getModulePaths($moduleName, $type) as $modulePath) {
                            $fullPath = $modulesPath . '/' . $moduleName . '/' . $modulePath;
                            if (is_dir($fullPath)) {
                                $this->scanDirectory(
                                    $fullPath,
                                    $structureResolver->getModuleNamespace($moduleName, $type, $modulePath),
                                    $eventDispatcher,
                                    $container
                                );
                            }
                        }
                    }
                } catch (\InvalidArgumentException) {
                    continue;
                }
            }
        }
    }

    protected function commands(): array
    {
        return [
            \Capability\ForgeEvents\Commands\QueueWorkCommand::class,
        ];
    }

    private function scanDirectory(
        string $dir,
        string $namespace,
        EventDispatcher $eventDispatcher,
        Container $container,
    ): void {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen($dir) + 1);
            $fqcn = $namespace . '\\' . str_replace('/', '\\', substr($relativePath, 0, -4));

            if (!class_exists($fqcn)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($fqcn);
                foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    foreach ($method->getAttributes(EventListener::class) as $attribute) {
                        $listener = $attribute->newInstance();
                        if ($container->has($fqcn)) {
                            $eventDispatcher->addListener($listener->eventClass, [$container->get($fqcn), $method->getName()]);
                        } else {
                            $eventDispatcher->addListener($listener->eventClass, [$fqcn, $method->getName()]);
                        }
                    }
                }
            } catch (ReflectionException) {
                continue;
            }
        }
    }

    private function setupConfigDefaults(Container $container): void
    {
        $config = $container->get(Config::class);
        $config->set('forge_events.queue_driver', env('QUEUE_DRIVER', 'database'));
        $config->set('forge_events.queue_list', env('QUEUE_LIST', ['default', 'cron', 'pipelines']));
        $config->set('forge_events.scheduled_events', env('FORGE_EVENTS_SCHEDULED', []));
    }
}
