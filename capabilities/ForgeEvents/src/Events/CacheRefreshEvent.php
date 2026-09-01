<?php

declare(strict_types=1);

namespace Capability\ForgeEvents\Events;

use Capability\ForgeEvents\Attributes\Event;
use Capability\ForgeEvents\Enums\QueuePriority;

#[Event(queue: 'cache_refresh', maxRetries: 3, delay: '0s', priority: QueuePriority::LOW)]
final class CacheRefreshEvent
{
    public function __construct(
        public readonly string  $instanceClass,
        public readonly string  $method,
        public readonly array   $args,
        public readonly string  $key,
        public readonly ?string $driver = null,
        public readonly ?int    $ttl = null,
        public readonly ?array  $tags = null
    ) {
    }
}
