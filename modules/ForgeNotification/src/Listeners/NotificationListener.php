<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Listeners;

use App\Modules\ForgeEvents\Attributes\EventListener;
use App\Modules\ForgeNotification\Channels\EmailChannel;
use App\Modules\ForgeNotification\Channels\PushChannel;
use App\Modules\ForgeNotification\Channels\SmsChannel;
use App\Modules\ForgeNotification\Events\EmailNotificationEvent;
use App\Modules\ForgeNotification\Events\PushNotificationEvent;
use App\Modules\ForgeNotification\Events\SmsNotificationEvent;
use App\Modules\ForgeNotification\Services\ChannelManager;
use Forge\Core\DI\Attributes\Service;

/**
 * Event listener for notification events.
 * Handles queued notifications and routes them to appropriate channels.
 */
#[Service]
final class NotificationListener
{
  public function __construct(
    private readonly ChannelManager $channelManager
  ) {
  }

  /**
   * Handle email notification event.
   *
   * @param EmailNotificationEvent $event
   * @return void
   */
  #[EventListener(EmailNotificationEvent::class)]
  public function handleEmailNotification(EmailNotificationEvent $event): void
  {
    $channel = $this->channelManager->email();
    $channel->send($event->notification, $event->provider);
  }

  /**
   * Handle SMS notification event.
   *
   * @param SmsNotificationEvent $event
   * @return void
   */
  #[EventListener(SmsNotificationEvent::class)]
  public function handleSmsNotification(SmsNotificationEvent $event): void
  {
    $channel = $this->channelManager->sms();
    $channel->send($event->notification, $event->provider);
  }

  /**
   * Handle push notification event.
   *
   * @param PushNotificationEvent $event
   * @return void
   */
  #[EventListener(PushNotificationEvent::class)]
  public function handlePushNotification(PushNotificationEvent $event): void
  {
    $channel = $this->channelManager->push();
    $channel->send($event->notification, $event->provider);
  }
}
