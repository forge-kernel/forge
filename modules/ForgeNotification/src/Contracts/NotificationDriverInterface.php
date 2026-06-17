<?php

namespace App\Modules\ForgeNotification\Contracts;

interface NotificationDriverInterface
{
    public function send(array $data): bool;
}
