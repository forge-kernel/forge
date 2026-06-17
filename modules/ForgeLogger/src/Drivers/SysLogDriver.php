<?php

declare(strict_types=1);

namespace App\Modules\ForgeLogger\Drivers;

use App\Modules\ForgeLogger\Contracts\LogDriverInterface;

final class SysLogDriver implements LogDriverInterface
{
    public function write(string $message): void
    {
        syslog(LOG_INFO, $message);
    }
}
