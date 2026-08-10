<?php

declare(strict_types=1);

namespace Modules\ForgeEvents\Listeners;

use Modules\ForgeEvents\Attributes\EventListener;
use Modules\ForgeEvents\Events\CacheRefreshEvent;
use Forge\Core\Cache\CacheManager;
use Forge\Core\Cache\ProxyMarkerInterface;
use Forge\Core\DI\Container;

final readonly class CacheRefreshListener
{
    public function __construct(
        private CacheManager $cache,
        private Container $container,
    ) {
    }

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    #[EventListener(CacheRefreshEvent::class)]
    public function handle(CacheRefreshEvent $event): void
    {
        $manager = $event->driver ? new CacheManager($event->driver) : $this->cache;

        $instance = $this->container->make($event->instanceClass);

        // Never re-invoke the cached method through a cache proxy (would loop).
        if ($instance instanceof ProxyMarkerInterface) {
            $prop = new \ReflectionProperty($instance, '__real');
            $instance = $prop->getValue($instance);
        }

        $method = new \ReflectionMethod($instance, $event->method);
        $result = $method->invokeArgs($instance, $event->args);

        if ($event->tags && method_exists($manager, 'tags')) {
            $manager->tags($event->tags)->set($event->key, $result, $event->ttl);
        } else {
            $manager->set($event->key, $result, $event->ttl);
        }
    }
}
