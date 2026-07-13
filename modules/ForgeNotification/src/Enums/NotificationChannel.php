<?php

declare(strict_types=1);

namespace Modules\ForgeNotification\Enums;

enum NotificationChannel: string
{
    case email = 'email';
    case sms = 'sms';
    case push = 'push';
}
