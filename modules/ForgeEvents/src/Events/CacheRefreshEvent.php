<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Events;

use App\Modules\ForgeEvents\Attributes\Event;
use App\Modules\ForgeEvents\Enums\QueuePriority;

#[Event(queue: 'cache_refresh', maxRetries: 3, delay: '0s', priority: QueuePriority::LOW)]
final class CacheRefreshEvent
{
    public function __construct(
        public readonly object  $instance,
        public readonly string  $method,
        public readonly array   $args,
        public readonly string  $key,
        public readonly ?string $driver = null,
        public readonly ?int    $ttl = null,
        public readonly ?array  $tags = null
    ) {
    }
}
