<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Contracts;

use App\Modules\ForgeNotification\Dto\NotificationDto;

/**
 * Interface for notification providers (Twilio, SMTP, SendGrid, etc.).
 * Providers are concrete implementations that actually send notifications.
 */
interface ProviderInterface
{
  /**
   * Send a notification using this provider.
   *
   * @param NotificationDto $notification The notification data
   * @return bool True if sent successfully, false otherwise
   */
  public function send(NotificationDto $notification): bool;

  /**
   * Get the provider name.
   *
   * @return string
   */
  public function getName(): string;

  /**
   * Validate that the provider is properly configured.
   *
   * @return bool True if valid, false otherwise
   */
  public function validate(): bool;
}
