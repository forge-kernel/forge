<?php

declare(strict_types=1);

namespace App\Modules\ForgeLogger\Contracts;

interface LogDriverInterface
{
    public function write(string $message): void;
}
