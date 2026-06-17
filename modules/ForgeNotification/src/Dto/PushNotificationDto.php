<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Dto;

/**
 * Push notification DTO with push-specific fields.
 */
final class PushNotificationDto extends NotificationDto
{
  public function __construct(
    string|array $to,
    public string $title,
    public ?string $body = null,
    ?string $from = null,
    public ?array $data = null,
    public ?int $badge = null,
    public ?string $sound = null,
    public ?string $icon = null,
    public ?string $image = null,
    public ?string $clickAction = null,
    public ?array $metadata = null,
  ) {
    parent::__construct($to, $from, $title, $body, $metadata);
  }
}
