<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Services;

use App\Modules\ForgeEvents\Exceptions\EventException;
use App\Modules\ForgeEvents\Services\EventDispatcher;
use App\Modules\ForgeNotification\Channels\EmailChannel;
use App\Modules\ForgeNotification\Channels\PushChannel;
use App\Modules\ForgeNotification\Channels\SmsChannel;
use App\Modules\ForgeNotification\Dto\EmailNotificationDto;
use App\Modules\ForgeNotification\Dto\PushNotificationDto;
use App\Modules\ForgeNotification\Dto\SmsNotificationDto;
use App\Modules\ForgeNotification\Events\EmailNotificationEvent;
use App\Modules\ForgeNotification\Events\PushNotificationEvent;
use App\Modules\ForgeNotification\Events\SmsNotificationEvent;
use Forge\Core\Contracts\NotificationInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;

/**
 * Main notification service with fluent API.
 * Provides easy-to-use methods for sending notifications via multiple channels.
 * Supports both synchronous (immediate) and asynchronous (queued) sending.
 */
#[Service]
#[Provides(interface: NotificationInterface::class, version: '0.2.0')]
final class ForgeNotificationService implements NotificationInterface
{
  public function __construct(
    private readonly ChannelManager $channelManager,
    private readonly EventDispatcher $eventDispatcher
  ) {
  }

  /**
   * Get the email channel.
   *
   * @return EmailChannel
   */
  public function email(): EmailChannel
  {
    return $this->channelManager->email();
  }

  /**
   * Get the SMS channel.
   *
   * @return SmsChannel
   */
  public function sms(): SmsChannel
  {
    return $this->channelManager->sms();
  }

  /**
   * Get the push notification channel.
   *
   * @return PushChannel
   */
  public function push(): PushChannel
  {
    return $this->channelManager->push();
  }

  /**
   * Queue an email notification for async sending.
   *
   * @param EmailNotificationDto $notification
   * @param string|null $provider Optional provider override
   * @return void
   * @throws EventException
   */
  public function queueEmail(EmailNotificationDto $notification, ?string $provider = null): void
  {
    $this->eventDispatcher->dispatch(
      new EmailNotificationEvent($notification, $provider)
    );
  }

  /**
   * Queue an SMS notification for async sending.
   *
   * @param SmsNotificationDto $notification
   * @param string|null $provider Optional provider override
   * @return void
   * @throws EventException
   */
  public function queueSms(SmsNotificationDto $notification, ?string $provider = null): void
  {
    $this->eventDispatcher->dispatch(
      new SmsNotificationEvent($notification, $provider)
    );
  }

  /**
   * Queue a push notification for async sending.
   *
   * @param PushNotificationDto $notification
   * @param string|null $provider Optional provider override
   * @return void
   * @throws EventException
   */
  public function queuePush(PushNotificationDto $notification, ?string $provider = null): void
  {
    $this->eventDispatcher->dispatch(
      new PushNotificationEvent($notification, $provider)
    );
  }
}
