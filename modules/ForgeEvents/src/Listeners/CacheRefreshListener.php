<?php

declare(strict_types=1);

namespace Modules\ForgeEvents\Listeners;

use Modules\ForgeEvents\Attributes\EventListener;
use Modules\ForgeEvents\Events\CacheRefreshEvent;
use Forge\Core\Cache\CacheManager;

final readonly class CacheRefreshListener
{
    public function __construct(private CacheManager $cache)
    {
    }

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    #[EventListener(CacheRefreshEvent::class)]
    public function handle(CacheRefreshEvent $event): void
    {
        $manager = $event->driver ? new CacheManager($event->driver) : $this->cache;

        $method = new \ReflectionMethod($event->instance, $event->method);
        $result = $method->invokeArgs($event->instance, $event->args);

        if ($event->tags && method_exists($manager, 'tags')) {
            $manager->tags($event->tags)->set($event->key, $result, $event->ttl);
        } else {
            $manager->set($event->key, $result, $event->ttl);
        }
    }
}
