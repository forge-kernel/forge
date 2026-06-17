<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Services;

use App\Modules\ForgeEvents\Queues\DatabaseQueue;
use App\Modules\ForgeSqlOrm\ORM\Paginator;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Url;

#[Service]
class QueueHubService
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder
    )
    {
    }

    public function getJobs(
        array  $filters,
        string $sortColumn,
        string $sortDirection,
        int    $page,
        int    $perPage,
        bool   $includeDetails = false
    ): Paginator
    {
        $countQuery = $this->buildFilteredQuery($filters);
        $total = $countQuery->count();

        $dataQuery = $this->buildFilteredQuery($filters);
        $offset = ($page - 1) * $perPage;
        $items = $dataQuery
            ->orderBy($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->get();

        $processedItems = [];
        foreach ($items as $item) {
            $processedItems[] = $this->enrichJobData($item, $includeDetails);
        }

        return new Paginator(
            items: $processedItems,
            total: $total,
            perPage: $perPage,
            currentPage: $page,
            cursor: null,
            sortColumn: $sortColumn,
            sortDirection: $sortDirection,
            filters: $filters,
            search: $filters['search'] ?? null,
            searchFields: [],
            baseUrl: Url::baseUrl() . '/hub/queues',
            queryParams: []
        );
    }

    public function getJobDetails(int $jobId): ?array
    {
        $job = $this->queryBuilder->reset()
            ->setTable('queue_jobs')
            ->where('id', '=', $jobId)
            ->first();

        if (!$job) {
            return null;
        }

        return $this->enrichJobData($job, true);
    }

    public function getJobStatus(array $job): string
    {
        if (!empty($job['failed_at'])) {
            return 'failed';
        }
        if (!empty($job['reserved_at'])) {
            return 'processing';
        }
        if (!empty($job['process_at'])) {
            $processAt = strtotime($job['process_at']);
            if ($processAt > time()) {
                return 'scheduled';
            }
        }
        return 'pending';
    }

    public function getQueues(): array
    {
        return array_column($this->queryBuilder->reset()
            ->setTable('queue_jobs')
            ->select('DISTINCT queue')
            ->get(), 'queue');
    }

    public function getStats(): array
    {
        $now = date('Y-m-d H:i:s');

        $stats = $this->queryBuilder->reset()
            ->setTable('queue_jobs')
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("COUNT(CASE WHEN failed_at IS NOT NULL THEN 1 END) as failed")
            ->selectRaw("COUNT(CASE WHEN reserved_at IS NOT NULL AND failed_at IS NULL THEN 1 END) as processing")
            ->selectRaw("
            COUNT(CASE WHEN
                reserved_at IS NULL AND
                failed_at IS NULL AND
                (process_at IS NULL OR process_at <= :now)
            THEN 1 END) as pending",
                ['now' => $now]
            )
            ->first();

        return [
            'total' => (int)($stats['total'] ?? 0),
            'pending' => (int)($stats['pending'] ?? 0),
            'processing' => (int)($stats['processing'] ?? 0),
            'failed' => (int)($stats['failed'] ?? 0),
        ];
    }

    public function retryJob(int $jobId): bool
    {
        try {
            $job = $this->queryBuilder->reset()
                ->setTable('queue_jobs')
                ->where('id', '=', $jobId)
                ->first();

            if (!$job || empty($job['failed_at'])) {
                return false;
            }

            $payload = $this->parsePayload($job['payload']);
            if (!$payload) {
                return false;
            }

            $databaseQueue = new DatabaseQueue($this->queryBuilder);
            $databaseQueue->push(
                $job['payload'],
                (int)($job['priority'] ?? 100),
                0,
                (int)($job['max_retries'] ?? 1),
                $job['queue'] ?? 'default'
            );

            $this->queryBuilder->reset()
                ->setTable('queue_jobs')
                ->where('id', '=', $jobId)
                ->delete();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteJob(int $jobId): bool
    {
        try {
            $this->queryBuilder->reset()
                ->setTable('queue_jobs')
                ->where('id', '=', $jobId)
                ->delete();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function triggerJob(int $jobId): bool
    {
        try {
            $job = $this->queryBuilder->reset()
                ->setTable('queue_jobs')
                ->where('id', '=', $jobId)
                ->first();

            if (!$job) {
                return false;
            }

            $this->queryBuilder->reset()
                ->setTable('queue_jobs')
                ->where('id', '=', $jobId)
                ->update([
                    'reserved_at' => null,
                    'process_at' => date('Y-m-d H:i:s'),
                ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function parsePayload(string $payload): ?array
    {
        try {
            $data = @unserialize($payload);
            if ($data === false) {
                return null;
            }

            if (!is_array($data)) {
                return null;
            }

            return [
                'class' => $data['class'] ?? null,
                'event' => $data['event'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'attempts' => $data['attempts'] ?? 0,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildFilteredQuery(array $filters): QueryBuilderInterface
    {
        $query = $this->queryBuilder->reset()->setTable('queue_jobs');
        $now = date('Y-m-d H:i:s');

        if (!empty($filters['status'])) {
            $status = $filters['status'];
            $query = match ($status) {
                'pending' => $query->whereNull('reserved_at')
                    ->whereNull('failed_at')
                    ->whereRaw('(process_at IS NULL OR process_at <= :now)', ['now' => $now]),
                'processing' => $query->whereNotNull('reserved_at')
                    ->whereNull('failed_at'),
                'failed' => $query->whereNotNull('failed_at'),
                'scheduled' => $query->whereRaw('process_at > :now', ['now' => $now])
                    ->whereNull('reserved_at')
                    ->whereNull('failed_at'),
                default => $query,
            };
        }

        if (!empty($filters['queue'])) {
            $query = $query->where('queue', '=', $filters['queue']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query = $query->whereRaw('(payload LIKE :search OR queue LIKE :search)', [
                'search' => "%{$search}%"
            ]);
        }

        return $query;
    }

    private function enrichJobData(array $job, bool $includeDetails = false): array
    {
        $status = $this->getJobStatus($job);
        $payloadData = $this->parsePayload($job['payload'] ?? '');

        $enriched = [
            'id' => (int)$job['id'],
            'queue' => $job['queue'] ?? 'default',
            'status' => $status,
            'priority' => (int)($job['priority'] ?? 100),
            'attempts' => (int)($job['attempts'] ?? 0),
            'max_retries' => (int)($job['max_retries'] ?? 1),
            'created_at' => $job['created_at'] ?? null,
            'process_at' => $job['process_at'] ?? null,
            'reserved_at' => $job['reserved_at'] ?? null,
            'failed_at' => $job['failed_at'] ?? null,
            'event_class' => $payloadData['class'] ?? null,
        ];

        if ($includeDetails) {
            $enriched['details'] = [
                'payload' => $payloadData,
                'metadata' => [
                    'queue' => $job['queue'] ?? 'default',
                    'priority' => (int)($job['priority'] ?? 100),
                    'attempts' => (int)($job['attempts'] ?? 0),
                    'max_retries' => (int)($job['max_retries'] ?? 1),
                ],
            ];
        }

        return $enriched;
    }
}
