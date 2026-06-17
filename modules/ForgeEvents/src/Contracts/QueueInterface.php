<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Contracts;

interface Queueinterface
{
    public function push(
        string $payload,
        int $priority = 0,
        int $delayMilliseconds = 0,
        int $maxRetries = 3,
        string $queue = 'default'
    ): void;
    public function pop(string $queue = 'default'): ?array;
    public function count(): int;
    public function clear(): void;
    public function release(int $jobId, int $delay = 0): void;
    public function getNextJobDelay(string $queue = 'default'): ?float;
}
