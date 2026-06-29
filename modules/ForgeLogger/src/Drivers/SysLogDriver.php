<?php

declare(strict_types=1);

namespace Modules\ForgeLogger\Drivers;

use Modules\ForgeLogger\Contracts\LogDriverInterface;

final class SysLogDriver implements LogDriverInterface
{
    public function write(string $message): void
    {
        syslog(LOG_INFO, $message);
    }
}
