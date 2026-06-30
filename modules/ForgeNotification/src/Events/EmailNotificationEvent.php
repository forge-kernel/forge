<?php

declare(strict_types=1);

namespace Modules\ForgeNotification\Events;

use Modules\ForgeEvents\Attributes\Event;
use Modules\ForgeEvents\Enums\QueuePriority;
use Modules\ForgeNotification\Dto\EmailNotificationDto;

/**
 * Event for queued email notifications.
 * Dispatched when an email notification should be sent asynchronously.
 */
#[Event(
    queue: "notifications",
    maxRetries: 3,
    delay: "0s",
    priority: QueuePriority::NORMAL,
)]
final readonly class EmailNotificationEvent
{
    public function __construct(
        public EmailNotificationDto $notification,
        public ?string $provider = null
    ) {
    }
}
