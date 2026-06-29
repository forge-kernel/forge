<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Services;

interface ObservabilityServiceInterface
{
    /**
     * Get aggregated dashboard statistics for the given time window.
     *
     * @param int $hours Number of hours to look back.
     * @return array<string, mixed>
     */
    public function getDashboardStats(int $hours = 24): array;

    /**
     * Get a paginated list of traces with optional filters.
     *
     * @param array<string, mixed> $filters
     * @param int $page
     * @param int $perPage
     * @return array<string, mixed>
     */
    public function getTraces(array $filters = [], int $page = 1, int $perPage = 25): array;

    /**
     * Get full detail for a single trace, including decoded spans.
     *
     * @param string $id
     * @return array<string, mixed>|null
     */
    public function getTraceDetail(string $id): ?array;

    /**
     * Get aggregated slow query statistics.
     *
     * @param int $limit
     * @param float $minDurationMs
     * @return array<int, array<string, mixed>>
     */
    public function getSlowQueries(int $limit = 25, float $minDurationMs = 100): array;

    /**
     * Purge traces older than the given number of days.
     *
     * @param int $days
     * @return int Number of rows deleted.
     */
    public function purgeOldTraces(int $days = 7): int;
}
