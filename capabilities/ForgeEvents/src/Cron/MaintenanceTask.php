<?php

declare(strict_types=1);

namespace Capability\ForgeEvents\Cron;

/**
 * Base class for maintenance business logic. Each concrete task performs one
 * cycle of work and reports a MaintenanceResult. Errors are formatted through
 * formatError() so the manager can persist them on the job's metadata.
 */
abstract class MaintenanceTask
{
    /**
     * Run one cycle of the maintenance work.
     *
     * Return ok=false (optionally with formatted $errors) to report a failure
     * that the manager records and keeps on the job. Throw a \Throwable for a
     * fatal failure — the queue records it and retries per the event config.
     */
    abstract public function execute(): MaintenanceResult;

    /**
     * Properly format a thrown error for the job metadata (metadata.error).
     */
    protected function formatError(\Throwable $e): string
    {
        return sprintf(
            '[%s] %s: %s',
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
        );
    }
}