<?php
declare(strict_types=1);

namespace App\Modules\ForgeLogger\Contracts;

interface ForgeLoggerInterface
{
    public function registerDriver(string $name, LogDriverInterface $driver): void;

    public function log(string $message, string $level = 'INFO'): void;

    public function debug(string $message, array $context = []): void;
}
