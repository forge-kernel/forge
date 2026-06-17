<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Commands;

use App\Modules\ForgeEvents\Services\EventDispatcher;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;

#[Cli(
    command: 'queue:work',
    description: 'Process queued events',
    usage: 'queue:work [--workers=1] [--queues=queue1,queue2]',
    examples: [
        'queue:work --workers=2',
        'queue:work --queues=emails,notifications',
        'queue:work --workers=2 --queues=emails,notifications',
        'queue:work  (starts wizard)'
    ]
)]
final class QueueWorkCommand extends Command
{
    use Wizard;

    private static bool $shutdown = false;

    #[Arg(
        name: 'workers',
        description: 'Number of workers per queue',
        default: 1,
        required: false,
        validate: '/^\d+$/'
    )]
    private int $workers = 1;

    #[Arg(
        name: 'queues',
        description: 'Comma-separated list of queue names (overrides QUEUE_LIST)',
        default: null,
        required: false
    )]
    private ?string $queues = null;

    public function __construct(private readonly EventDispatcher $dispatcher)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        $workers = max(1, $this->workers);

        if ($this->queues !== null && !empty($this->queues)) {
            $queues = array_map('trim', explode(',', $this->queues));
            $queues = array_filter($queues, fn($q) => !empty($q));
            if (empty($queues)) {
                $this->error('Invalid queue list provided');
                return 1;
            }
        } else {
            $queues = env('QUEUE_LIST', ['default']);
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGINT, fn() => self::$shutdown = true);
        pcntl_signal(SIGTERM, fn() => self::$shutdown = true);

        foreach ($queues as $queue) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                $this->error("Unable to fork worker for queue {$queue}");
                return 1;
            }
            if ($pid === 0) {
                $this->spawnWorkers($queue, $workers);
                exit(0);
            }
        }

        while (pcntl_wait($status) !== -1) {
        }

        $this->info('All workers terminated.');
        return 0;
    }

    private function spawnWorkers(string $queue, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                $this->error("Unable to fork worker {$i}/{$count} for queue {$queue}");
                continue;
            }
            if ($pid === 0) {
                $this->workerLoop($queue);
                exit(0);
            }
        }

        while (pcntl_wait($status) !== -1) {
        }
    }

    private function workerLoop(string $queue): void
    {
        $pid = getmypid();
        $this->info("Worker for queue '{$queue}' started (PID {$pid})");

        $jobsHandled = 0;
        $backOff = 0.1;
        $maxBackOff = 5.0;
        $gcCycle = 50;
        $currentJobId = null;

        pcntl_signal(SIGTERM, function () use (&$currentJobId, $queue) {
            if ($currentJobId) {
                $this->dispatcher->release($currentJobId, 0);
            }
            exit(0);
        });

        while (!self::$shutdown) {
            pcntl_signal_dispatch();
            $currentJobId = null;
            $jobProcessed = false;

            while ($id = $this->dispatcher->processNextEvent($queue)) {
                $currentJobId = $id;
                $this->info("Queue {$queue} processed job {$id}");
                $jobsHandled++;
                $backOff = 0.1;
                $jobProcessed = true;

                if ($jobsHandled % $gcCycle === 0) {
                    gc_collect_cycles();
                }
            }

            if (!$jobProcessed) {
                $next = $this->dispatcher->getNextJobDelay($queue) ?? 0;
                $sleep = $next > 0 ? min($next, $maxBackOff) : $backOff;
                usleep((int)($sleep * 1_000_000));
                $backOff = min($backOff * 2, $maxBackOff);
            }
        }

        $this->warning("Worker for queue '{$queue}' (PID {$pid}) exiting gracefully.");
    }
}
