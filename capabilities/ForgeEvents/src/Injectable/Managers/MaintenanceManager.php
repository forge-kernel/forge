<?php

declare(strict_types=1);

namespace Capability\ForgeEvents\Injectable\Managers;

use Capability\ForgeEvents\Attributes\Event;
use Capability\ForgeEvents\Attributes\Schedule;
use Capability\ForgeEvents\Cron\MaintenanceResult;
use Capability\ForgeEvents\Cron\MaintenanceTask;
use Capability\ForgeEvents\Services\EventDispatcher;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Injectable;
use Forge\Core\DI\Container;
use ReflectionClass;

/**
 * The cron runner for self-rescheduling maintenance jobs. It resolves the task
 * behind an event (from its #[Schedule] attribute), executes one cycle, persists
 * formatted errors onto the job's metadata and re-schedules the next run on the
 * event's queue.
 *
 * Usage from a listener:
 *   $manager->run(new SomeMaintenanceEvent());
 *
 * Usage to seed the chain from an HTTP request (idempotent):
 *   $manager->seed(new SomeMaintenanceEvent());
 */
#[Injectable]
final class MaintenanceManager
{
    /**
     * A reserved job older than this (seconds) is considered stuck after a
     * worker crash and is re-seeded by seedAll. Fresh reservations just mean
     * the worker is processing the chain right now.
     */
    private const int RESERVED_RECLAIM_SECONDS = 600;

    public function __construct(
        private readonly Container $container,
        private readonly EventDispatcher $dispatcher,
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    /**
     * Execute one cycle of the event's maintenance task, persist any reported
     * errors on the job metadata, and always re-schedule the next run so the
     * cron chain stays alive even when the task fails.
     */
    public function run(object $event): MaintenanceResult
    {
        $schedule = $this->scheduleFor($event);
        $task = $this->resolveTask($schedule->task);

        try {
            $result = $task->execute();
        } finally {
            $this->reschedule($event, $schedule);
        }

        if (!$result->ok || $result->errors !== []) {
            $this->recordFailure($result);
        }

        return $result;
    }

    /**
     * Seed the maintenance chain from HTTP if no pending job already exists for
     * this event. Idempotent — safe to call on every request.
     */
    public function seed(object $event): void
    {
        if ($this->hasPending($event)) {
            return;
        }

        $this->dispatcher->dispatch($event);
    }

    /**
     * Seed every scheduled maintenance event (from the supplied list) that does
     * not already have a live job on its queue. One batched query over the
     * cron + pipelines queues — no N+1 — and only the missing chains are
     * dispatched.
     *
     * A job counts as "live" while it is pending OR freshly reserved (the queue
     * worker is currently processing it) — otherwise, with a worker running,
     * every request would re-seed the whole chain whenever the worker happened
     * to have reserved the jobs. Only chains whose reservation is older than
     * the reclaim window (stuck after a worker crash) are considered dead and
     * re-seeded. Idempotent, so it is safe to run on every request.
     *
     * @param array<int, class-string> $eventClasses
     */
    public function seedAll(array $eventClasses): void
    {
        if ($eventClasses === []) {
            return;
        }

        $staleBefore = date('Y-m-d H:i:s', time() - self::RESERVED_RECLAIM_SECONDS);

        $pending = [];
        $rows = $this->queryBuilder
            ->reset()
            ->setTable('queue_jobs')
            ->whereIn('queue', $this->seedQueues())
            ->whereNull('failed_at')
            ->select('payload', 'reserved_at')
            ->get();

        foreach ($rows as $row) {
            $payload = (string) ($row['payload'] ?? '');
            $reservedAt = (string) ($row['reserved_at'] ?? '');
            foreach ($eventClasses as $class) {
                if (isset($pending[$class])) {
                    continue;
                }
                if (!str_contains($payload, $class)) {
                    continue;
                }
                // Live = pending, or a reservation still fresh enough that the
                // worker is processing it. Only a reservation older than the
                // reclaim window (stuck after a worker crash) counts as dead.
                $stuck = $reservedAt !== '' && $reservedAt < $staleBefore;
                if (!$stuck) {
                    $pending[$class] = true;
                }
            }
        }

        foreach ($eventClasses as $class) {
            if (isset($pending[$class]) || !class_exists($class)) {
                continue;
            }
            $this->dispatcher->dispatch(new $class());
        }
    }

    /**
     * The queues scheduled maintenance events land on.
     *
     * @return array<int, string>
     */
    private function seedQueues(): array
    {
        return ['cron', 'pipelines'];
    }

    private function resolveTask(string $taskClass): MaintenanceTask
    {
        $task = $this->container->make($taskClass);

        if (!$task instanceof MaintenanceTask) {
            throw new \RuntimeException(
                sprintf('Maintenance task "%s" must extend %s.', $taskClass, MaintenanceTask::class),
            );
        }

        return $task;
    }

    private function scheduleFor(object $event): Schedule
    {
        $reflection = new ReflectionClass($event);
        $attributes = $reflection->getAttributes(Schedule::class);

        if ($attributes === []) {
            throw new \RuntimeException(
                sprintf('Maintenance event %s is missing the #[Schedule] attribute.', $reflection->getName()),
            );
        }

        return $attributes[0]->newInstance();
    }

    private function queueFor(object $event): string
    {
        $reflection = new ReflectionClass($event);
        $attributes = $reflection->getAttributes(Event::class);

        if ($attributes === []) {
            throw new \RuntimeException(
                sprintf('Maintenance event %s is missing the #[Event] attribute.', $reflection->getName()),
            );
        }

        return $attributes[0]->newInstance()->queue;
    }

    private function hasPending(object $event): bool
    {
        $queue = $this->queueFor($event);

        return $this->queryBuilder
            ->setTable('queue_jobs')
            ->where('queue', '=', $queue)
            ->whereNull('reserved_at')
            ->whereNull('failed_at')
            ->where('payload', 'LIKE', '%' . get_class($event) . '%')
            ->count() > 0;
    }

    private function reschedule(object $event, Schedule $schedule): void
    {
        try {
            $delay = $this->dispatcher->toMilliseconds($schedule->every);
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException(
                sprintf('Invalid #[Schedule(every: "%s")] interval.', $schedule->every),
                0,
                $e,
            );
        }

        $this->dispatcher->dispatchDelayed($event, $delay);
    }

    /**
     * Persist a reported failure on the job's metadata (metadata.error /
     * metadata.errors) and mark the job failed so its row is kept. The chain
     * continues via the reschedule in run().
     */
    private function recordFailure(MaintenanceResult $result): void
    {
        $jobId = $this->dispatcher->currentJobId();

        if ($jobId === null) {
            return;
        }

        $metadata = [];

        if ($result->errors !== []) {
            $metadata['errors'] = $result->errors;
        }

        $metadata['error'] = $result->message !== ''
            ? $result->message
            : (implode('; ', $result->errors) ?: 'Maintenance run reported a failure.');

        $this->dispatcher->writeJobMetadata($jobId, $metadata);
        $this->dispatcher->failJob($jobId);
    }
}