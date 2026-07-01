<?php

declare(strict_types=1);

namespace Modules\ForgeEvents;

use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Requires;
use Modules\ForgeEvents\Attributes\EventListener;
use Forge\Core\Bootstrap\OptimizedDirectoryScanner;
use Modules\ForgeEvents\Services\EventDispatcher;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Services\AttributeDiscoveryService;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

#[Module(
    name: "ForgeEvents",
    version: "1.4.11",
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
        'queue_list' => ['default'],
    ]
])]
final class ForgeEventsModule
{
    public function register(Container $container): void
    {
        $this->setupConfigDefaults($container);
        $eventDispatcher = $container->has(EventDispatcher::class)
            ? $container->get(EventDispatcher::class)
            : null;

        if (!$eventDispatcher) {
            return;
        }

        $discoveryService = new AttributeDiscoveryService();
        $basePaths = OptimizedDirectoryScanner::getAttributeDiscoveryPaths();
        $classMap = $discoveryService->discover($basePaths, [EventListener::class]);

        foreach ($classMap as $className => $metadata) {
            if (!class_exists($className)) {
                $filepath = $metadata['file'] ?? '';
                if ($filepath && file_exists($filepath)) {
                    require_once $filepath;
                }
            }

            if (!class_exists($className)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($className);
                $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

                foreach ($methods as $method) {
                    $attributes = $method->getAttributes(EventListener::class);
                    foreach ($attributes as $attribute) {
                        $listener = $attribute->newInstance();
                        $eventClass = $listener->eventClass;

                        $listenerInstance = $container->has($className)
                            ? $container->get($className)
                            : $reflection->newInstance();

                        $eventDispatcher->addListener($eventClass, [$listenerInstance, $method->getName()]);
                    }
                }
            } catch (ReflectionException $e) {

            }
        }
    }

    private function setupConfigDefaults(Container $container): void
    {
        $config = $container->get(Config::class);
        $config->set('forge_events.queue_driver', env('QUEUE_DRIVER', 'database'));
        $config->set('forge_events.queue_list', env('QUEUE_LIST', ['default']));
    }
}
