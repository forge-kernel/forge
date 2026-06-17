<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Contracts;

use App\Modules\ForgeNotification\Dto\NotificationDto;

/**
 * Interface for notification channels (email, sms, push).
 * Channels are semantic notification types that route to providers.
 */
interface ChannelInterface
{
  /**
   * Send a notification through this channel.
   * If $notification is null, builds notification from fluent builder properties.
   *
   * @param NotificationDto|null $notification The notification data (null for fluent API)
   * @param string|null $provider Optional provider name to use (overrides default)
   * @return bool True if sent successfully, false otherwise
   */
  public function send(?NotificationDto $notification = null, ?string $provider = null): bool;

  /**
   * Set the provider to use for this channel.
   *
   * @param string $provider Provider name
   * @return $this
   */
  public function via(string $provider): self;

  /**
   * Get the channel name.
   *
   * @return string
   */
  public function getName(): string;

  /**
   * Get the default provider for this channel.
   *
   * @return string
   */
  public function getDefaultProvider(): string;
}
