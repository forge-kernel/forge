<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Channels;

use App\Modules\ForgeEvents\Exceptions\EventException;
use App\Modules\ForgeEvents\Services\EventDispatcher;
use App\Modules\ForgeNotification\Contracts\ChannelInterface;
use App\Modules\ForgeNotification\Dto\NotificationDto;
use App\Modules\ForgeNotification\Dto\PushNotificationDto;
use App\Modules\ForgeNotification\Events\PushNotificationEvent;
use App\Modules\ForgeNotification\Services\ProviderResolver;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;

/**
 * Push notification channel.
 * Routes push notifications to configured push providers (Firebase, OneSignal, etc.).
 */
#[Service]
final class PushChannel implements ChannelInterface
{
  private ?string $selectedProvider = null;

  public function __construct(
    private readonly ProviderResolver $providerResolver,
    private readonly Config $config,
    private readonly EventDispatcher $eventDispatcher
  ) {
  }

  /**
   * Set the recipient device token(s) or user ID(s).
   *
   * @param string|array $to Device token(s) or user ID(s)
   * @return $this
   */
  public function to(string|array $to): self
  {
    $this->to = $to;
    return $this;
  }

  /**
   * Set the notification title.
   *
   * @param string $title Notification title
   * @return $this
   */
  public function title(string $title): self
  {
    $this->title = $title;
    return $this;
  }

  /**
   * Set the notification body.
   *
   * @param string $body Notification body
   * @return $this
   */
  public function body(string $body): self
  {
    $this->body = $body;
    return $this;
  }

  /**
   * Set additional data payload.
   *
   * @param array $data Additional data
   * @return $this
   */
  public function data(array $data): self
  {
    $this->data = $data;
    return $this;
  }

  /**
   * Set the badge count.
   *
   * @param int $badge Badge number
   * @return $this
   */
  public function badge(int $badge): self
  {
    $this->badge = $badge;
    return $this;
  }

  /**
   * Set the sound file name.
   *
   * @param string $sound Sound file name
   * @return $this
   */
  public function sound(string $sound): self
  {
    $this->sound = $sound;
    return $this;
  }

  /**
   * Set the icon name.
   *
   * @param string $icon Icon name
   * @return $this
   */
  public function icon(string $icon): self
  {
    $this->icon = $icon;
    return $this;
  }

  /**
   * Set the image URL.
   *
   * @param string $image Image URL
   * @return $this
   */
  public function image(string $image): self
  {
    $this->image = $image;
    return $this;
  }

  /**
   * Set the click action.
   *
   * @param string $clickAction Click action URL or intent
   * @return $this
   */
  public function clickAction(string $clickAction): self
  {
    $this->clickAction = $clickAction;
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
    if ($notification === null) {
      if (!isset($this->to)) {
        throw new \RuntimeException('Push notification recipient (to) is required');
      }

      if (!isset($this->title)) {
        throw new \RuntimeException('Push notification title is required');
      }

      if (!isset($this->body)) {
        throw new \RuntimeException('Push notification body is required');
      }

      $notification = new PushNotificationDto(
        to: $this->to,
        title: $this->title,
        body: $this->body,
        from: null,
        data: $this->data ?? null,
        badge: $this->badge ?? null,
        sound: $this->sound ?? null,
        icon: $this->icon ?? null,
        image: $this->image ?? null,
        clickAction: $this->clickAction ?? null,
        metadata: $this->metadata ?? null
      );
    }

    if (!$notification instanceof PushNotificationDto) {
      throw new \InvalidArgumentException('PushChannel requires PushNotificationDto');
    }

    return $this->sendNotification($notification, $provider);
  }

  /**
   * Internal method to send notification via provider.
   */
  private function sendNotification(PushNotificationDto $notification, ?string $provider = null): bool
  {
    $providerName = $provider ?? $this->selectedProvider ?? $this->getDefaultProvider();
    $provider = $this->providerResolver->resolve($this->getName(), $providerName);

    return $provider->send($notification);
  }

  /**
   * Queue the push notification for async sending.
   *
   * @return void
   * @throws EventException
   */
  public function queue(): void
  {
    if (!isset($this->to)) {
      throw new \RuntimeException('Push notification recipient (to) is required');
    }

    if (!isset($this->title)) {
      throw new \RuntimeException('Push notification title is required');
    }

    if (!isset($this->body)) {
      throw new \RuntimeException('Push notification body is required');
    }

    $notification = new PushNotificationDto(
      to: $this->to,
      title: $this->title,
      body: $this->body,
      from: null,
      data: $this->data ?? null,
      badge: $this->badge ?? null,
      sound: $this->sound ?? null,
      icon: $this->icon ?? null,
      image: $this->image ?? null,
      clickAction: $this->clickAction ?? null,
      metadata: $this->metadata ?? null
    );

    $this->eventDispatcher->dispatch(
      new PushNotificationEvent($notification, $this->selectedProvider)
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
    return 'push';
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
  private ?string $title = null;
  private ?string $body = null;
  private ?array $data = null;
  private ?int $badge = null;
  private ?string $sound = null;
  private ?string $icon = null;
  private ?string $image = null;
  private ?string $clickAction = null;
  private ?array $metadata = null;
}
