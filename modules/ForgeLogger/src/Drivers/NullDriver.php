<?php

declare(strict_types=1);

namespace App\Modules\ForgeLogger\Drivers;

use App\Modules\ForgeLogger\Contracts\LogDriverInterface;

final class NullDriver implements LogDriverInterface
{
    public function write(string $message): void
    {
    }
}
