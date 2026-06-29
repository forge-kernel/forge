<?php

declare(strict_types=1);

namespace Modules\ForgeLogger\Contracts;

interface LogDriverInterface
{
    public function write(string $message): void;
}
