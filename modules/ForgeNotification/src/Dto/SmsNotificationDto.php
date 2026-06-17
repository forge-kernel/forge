<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Dto;

/**
 * SMS notification DTO with SMS-specific fields.
 */
final class SmsNotificationDto extends NotificationDto
{
  public function __construct(
    string|array $to,
    public string $message,
    ?string $from = null,
    public ?array $metadata = null,
  ) {
    parent::__construct($to, $from, null, $message, $metadata);
  }
}
