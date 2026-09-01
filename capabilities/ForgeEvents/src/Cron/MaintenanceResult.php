<?php

declare(strict_types=1);

namespace Capability\ForgeEvents\Cron;

/**
 * The outcome of one maintenance run cycle: whether it succeeded, a human
 * summary, per-key counts for observability, and any per-item errors that were
 * formatted for the job metadata.
 */
final readonly class MaintenanceResult
{
    /**
     * @param array<string, int> $counts
     * @param array<int, string> $errors Properly formatted per-item errors.
     */
    public function __construct(
        public bool $ok = true,
        public string $message = '',
        public array $counts = [],
        public array $errors = [],
    ) {}
}