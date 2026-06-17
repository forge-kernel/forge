<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Queues;

use App\Modules\ForgeEvents\Contracts\Queueinterface;
use App\Modules\ForgeEvents\Enums\QueuePriority;
use SplPriorityQueue;

final class InMemoryQueue implements Queueinterface
{
    /** @var array<string, \SplPriorityQueue> */
    private array $queues = [];
    private int $insertionCount = 0;

    public function __construct()
    {
        $this->queues = [];
    }

    public function push(
        string $payload,
        int $priority = 0,
        int $delayMs = 0,
        int $maxRetries = 3,
        string $queue = 'default'
    ): void {
        if (!isset($this->queues[$queue])) {
            $this->queues[$queue] = new \SplPriorityQueue();
            $this->queues[$queue]->setExtractFlags(\SplPriorityQueue::EXTR_DATA);
        }
        $this->queues[$queue]->insert([
            'payload' => $payload,
            'attempts' => 0,
            'queue' => $queue,
        ], [$priority, -$this->insertionCount++]);
    }

    public function pop(string $queue = 'default'): ?array
    {
        if (empty($this->queues[$queue]) || $this->queues[$queue]->isEmpty()) {
            return null;
        }
        return $this->queues[$queue]->extract();
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->queues as $q) {
            $count += $q->count();
        }
        return $count;
    }

    public function clear(): void
    {
        $this->queues = [];
        $this->insertionCount = 0;
    }

    public function release(int $jobId, int $delay = 0): void
    {
    }

    public function getNextJobDelay(string $queue = 'default'): ?float
    {
        return 0;
    }
}
