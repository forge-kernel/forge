<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Drivers;

use App\Modules\ForgeNotification\Contracts\NotificationDriverInterface;
use Forge\Core\Config\Config;

final class SmsDriver implements NotificationDriverInterface
{
    private string $accountSid;
    private string $authToken;
    private string $from;

    public function __construct(private Config $config)
    {
    }
    public function send(array $data): bool
    {
    }
}
