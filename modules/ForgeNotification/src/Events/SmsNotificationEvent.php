<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Events;

use App\Modules\ForgeEvents\Attributes\Event;
use App\Modules\ForgeEvents\Enums\QueuePriority;
use App\Modules\ForgeNotification\Dto\SmsNotificationDto;

/**
 * Event for queued SMS notifications.
 * Dispatched when an SMS notification should be sent asynchronously.
 */
#[
    Event(
    queue: "notifications",
    maxRetries: 3,
    delay: "0s",
    priority: QueuePriority::NORMAL,
  ),
  ]
final readonly class SmsNotificationEvent
{
  public function __construct(
    public SmsNotificationDto $notification,
    public ?string $provider = null
  ) {
  }
}
