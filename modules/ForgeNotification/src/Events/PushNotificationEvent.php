<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Events;

use App\Modules\ForgeEvents\Attributes\Event;
use App\Modules\ForgeEvents\Enums\QueuePriority;
use App\Modules\ForgeNotification\Dto\PushNotificationDto;

/**
 * Event for queued push notifications.
 * Dispatched when a push notification should be sent asynchronously.
 */
#[
    Event(
    queue: "notifications",
    maxRetries: 3,
    delay: "0s",
    priority: QueuePriority::NORMAL,
  ),
  ]
final readonly class PushNotificationEvent
{
  public function __construct(
    public PushNotificationDto $notification,
    public ?string $provider = null
  ) {
  }
}
