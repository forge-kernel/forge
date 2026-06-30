<?php

declare(strict_types=1);

namespace Modules\ForgeLogger\Contracts;

use Forge\Core\Contracts\LoggerInterface;

interface ForgeLoggerInterface extends LoggerInterface
{
    public function registerDriver(string $name, LogDriverInterface $driver): void;
}
