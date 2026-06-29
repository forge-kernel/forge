<?php

declare(strict_types=1);

namespace Modules\ForgeLogger\Drivers;

use Modules\ForgeLogger\Contracts\LogDriverInterface;

final class NullDriver implements LogDriverInterface
{
    public function write(string $message): void
    {
    }
}
