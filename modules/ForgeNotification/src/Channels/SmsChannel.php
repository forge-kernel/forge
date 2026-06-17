<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Channels;

use App\Modules\ForgeEvents\Exceptions\EventException;
use App\Modules\ForgeEvents\Services\EventDispatcher;
use App\Modules\ForgeNotification\Contracts\ChannelInterface;
use App\Modules\ForgeNotification\Dto\NotificationDto;
use App\Modules\ForgeNotification\Dto\SmsNotificationDto;
use App\Modules\ForgeNotification\Events\SmsNotificationEvent;
use App\Modules\ForgeNotification\Services\ProviderResolver;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;

/**
 * SMS notification channel.
 * Routes SMS notifications to configured SMS providers (Twilio, Vonage, etc.).
 */
#[Service]
final class SmsChannel implements ChannelInterface
{
  private ?string $selectedProvider = null;

  public function __construct(
    private readonly ProviderResolver $providerResolver,
    private readonly Config $config,
    private readonly EventDispatcher $eventDispatcher
  ) {
  }

  /**
   * Set the recipient phone number(s).
   *
   * @param string|array $to Phone number(s)
   * @return $this
   */
  public function to(string|array $to): self
  {
    $this->to = $to;
    return $this;
  }

  /**
   * Set the sender phone number.
   *
   * @param string $from Phone number
   * @return $this
   */
  public function from(string $from): self
  {
    $this->from = $from;
    return $this;
  }

  /**
   * Set the SMS message.
   *
   * @param string $message Message text
   * @return $this
   */
  public function message(string $message): self
  {
    $this->message = $message;
    return $this;
  }

  /**
   * Send a notification through this channel.
   * If $notification is null, builds notification from fluent builder properties.
   *
   * @param NotificationDto|null $notification The notification data (null for fluent API)
   * @param string|null $provider Optional provider name to use (overrides default)
   * @return bool True if sent successfully, false otherwise
   */
  public function send(?NotificationDto $notification = null, ?string $provider = null): bool
  {
    // If no notification provided, build from fluent properties
    if ($notification === null) {
      if (!isset($this->to)) {
        throw new \RuntimeException('SMS recipient (to) is required');
      }

      if (!isset($this->message)) {
        throw new \RuntimeException('SMS message is required');
      }

      $notification = new SmsNotificationDto(
        to: $this->to,
        message: $this->message,
        from: $this->from ?? null,
        metadata: $this->metadata ?? null
      );
    }

    if (!$notification instanceof SmsNotificationDto) {
      throw new \InvalidArgumentException('SmsChannel requires SmsNotificationDto');
    }

    return $this->sendNotification($notification, $provider);
  }

  /**
   * Internal method to send notification via provider.
   */
  private function sendNotification(SmsNotificationDto $notification, ?string $provider = null): bool
  {
    $providerName = $provider ?? $this->selectedProvider ?? $this->getDefaultProvider();
    $provider = $this->providerResolver->resolve($this->getName(), $providerName);

    return $provider->send($notification);
  }

  /**
   * Queue the SMS notification for async sending.
   *
   * @return void
   * @throws EventException
   */
  public function queue(): void
  {
    if (!isset($this->to)) {
      throw new \RuntimeException('SMS recipient (to) is required');
    }

    if (!isset($this->message)) {
      throw new \RuntimeException('SMS message is required');
    }

      $notification = new SmsNotificationDto(
        to: $this->to,
        message: $this->message,
        from: $this->from ?? null,
        metadata: $this->metadata ?? null
      );

    $this->eventDispatcher->dispatch(
      new SmsNotificationEvent($notification, $this->selectedProvider)
    );
  }

  /**
   * Set the provider to use for this channel.
   *
   * @param string $provider Provider name
   * @return $this
   */
  public function via(string $provider): self
  {
    $this->selectedProvider = $provider;
    return $this;
  }

  /**
   * Get the channel name.
   *
   * @return string
   */
  public function getName(): string
  {
    return 'sms';
  }

  /**
   * Get the default provider for this channel.
   *
   * @return string
   */
  public function getDefaultProvider(): string
  {
    return $this->providerResolver->getDefaultProvider($this->getName());
  }

  // Fluent builder properties
  private string|array|null $to = null;
  private ?string $from = null;
  private ?string $message = null;
  private ?array $metadata = null;
}
