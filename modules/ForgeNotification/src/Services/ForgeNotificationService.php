<?php

declare(strict_types=1);

namespace Modules\ForgeNotification\Services;

use Modules\ForgeEvents\Exceptions\EventException;
use Modules\ForgeEvents\Services\EventDispatcher;
use Modules\ForgeNotification\Channels\EmailChannel;
use Modules\ForgeNotification\Channels\PushChannel;
use Modules\ForgeNotification\Channels\SmsChannel;
use Modules\ForgeNotification\Dto\EmailNotificationDto;
use Modules\ForgeNotification\Dto\PushNotificationDto;
use Modules\ForgeNotification\Dto\SmsNotificationDto;
use Modules\ForgeNotification\Enums\NotificationChannel;
use Modules\ForgeNotification\Events\EmailNotificationEvent;
use Modules\ForgeNotification\Events\PushNotificationEvent;
use Modules\ForgeNotification\Events\SmsNotificationEvent;
use Modules\ForgeNotification\Payload\EmailPayload;
use Modules\ForgeNotification\Payload\PushPayload;
use Modules\ForgeNotification\Payload\SmsPayload;
use Forge\Core\Contracts\NotificationInterface;
use Forge\Core\Module\Attributes\Provides;

/**
 * Main notification service with fluent API.
 * Provides easy-to-use methods for sending notifications via multiple channels.
 * Supports both synchronous (immediate) and asynchronous (queued) sending.
 */
#[Provides(interface: NotificationInterface::class, version: '0.2.0')]
final class ForgeNotificationService implements NotificationInterface
{
    public function __construct(
        private readonly ChannelManager $channelManager,
        private readonly EventDispatcher $eventDispatcher
    ) {
    }

    /**
     * Send a notification via the specified channel.
     * This is the main entry point for sending notifications.
     *
     * @param NotificationChannel $channel The channel to send via
     * @param EmailPayload|SmsPayload|PushPayload $payload The notification data
     */
    public function send(NotificationChannel $channel, EmailPayload|SmsPayload|PushPayload $payload): void
    {
        match($channel) {
            NotificationChannel::email => $this->sendEmail($payload),
            NotificationChannel::sms => $this->sendSms($payload),
            NotificationChannel::push => $this->sendPush($payload),
        };
    }

    private function sendEmail(EmailPayload $payload): void
    {
        $builder = $this->email()
            ->to($payload->to);

        if ($payload->subject !== null) {
            $builder->subject($payload->subject);
        }

        if ($payload->html !== null) {
            $builder->html($payload->html);
        }

        if ($payload->text !== null) {
            $builder->text($payload->text);
        }

        if ($payload->from !== null) {
            $builder->from($payload->from);
        }

        if ($payload->cc !== null) {
            $builder->cc($payload->cc);
        }

        if ($payload->bcc !== null) {
            $builder->bcc($payload->bcc);
        }

        if ($payload->replyTo !== null) {
            $builder->replyTo($payload->replyTo);
        }

        if ($payload->attachments !== null) {
            $builder->attachments($payload->attachments);
        }

        if ($payload->via !== null) {
            $builder->via($payload->via);
        }

        $builder->send();
    }

    private function sendSms(SmsPayload $payload): void
    {
        $builder = $this->sms()
            ->to($payload->to)
            ->message($payload->message);

        if ($payload->from !== null) {
            $builder->from($payload->from);
        }

        if ($payload->via !== null) {
            $builder->via($payload->via);
        }

        $builder->send();
    }

    private function sendPush(PushPayload $payload): void
    {
        $builder = $this->push()
            ->to($payload->to);

        if ($payload->title !== null) {
            $builder->title($payload->title);
        }

        if ($payload->body !== null) {
            $builder->body($payload->body);
        }

        if ($payload->data !== null) {
            $builder->data($payload->data);
        }

        if ($payload->via !== null) {
            $builder->via($payload->via);
        }

        $builder->send();
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
