<?php

declare(strict_types=1);

namespace Capability\ForgeEvents\Attributes;

use Attribute;

/**
 * Declares a self-rescheduling maintenance job: how often it runs (the cron
 * cadence) and which task class executes it. The MaintenanceManager reads this
 * attribute to resolve the task and to re-schedule the next run.
 *
 * @see \Capability\ForgeEvents\Injectable\Managers\MaintenanceManager::run()
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Schedule
{
    /**
     * @param string $every How often the job runs, e.g. '15m', '1h', '1d', '7d', '1mo'.
     * @param class-string<\Capability\ForgeEvents\Cron\MaintenanceTask> $task The maintenance task that performs the work.
     */
    public function __construct(
        public readonly string $every,
        public readonly string $task,
    ) {}
}