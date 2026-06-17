<?php

declare(strict_types=1);

namespace App\Events;

use App\Modules\ForgeEvents\Attributes\Event;
use App\Modules\ForgeEvents\Enums\QueuePriority;

#[
    Event(
        queue: "page_visits",
        maxRetries: 5,
        delay: "1m",
        priority: QueuePriority::HIGH,
    ),
]
final readonly class TestPageVisitedEvent
{
    public function __construct(public int $userId, public string $visitedAt) {}
}
