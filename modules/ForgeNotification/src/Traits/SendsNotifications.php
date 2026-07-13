<?php

declare(strict_types=1);

namespace Modules\ForgeNotification\Traits;

use Forge\Core\DI\Container;
use Modules\ForgeNotification\Enums\NotificationChannel;
use Modules\ForgeNotification\Payload\EmailPayload;
use Modules\ForgeNotification\Payload\PushPayload;
use Modules\ForgeNotification\Payload\SmsPayload;
use Modules\ForgeNotification\Services\ForgeNotificationService;

trait SendsNotifications
{
    protected function sendNotification(NotificationChannel $channel, EmailPayload|SmsPayload|PushPayload $payload): void
    {
        try {
            Container::getInstance()
                ->get(ForgeNotificationService::class)
                ->send($channel, $payload);
        } catch (\Throwable $e) {
            if (function_exists('collect_exception')) {
                collect_exception($e);
            }
        }
    }
}
