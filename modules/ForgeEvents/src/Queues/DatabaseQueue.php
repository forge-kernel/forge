<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Queues;

use App\Modules\ForgeEvents\Contracts\QueueInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Attributes\Service;
use PDO;

#[Service(singleton: true)]
class DatabaseQueue implements QueueInterface
{
    private string $queueName = 'default';

    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    public function setQueue(string $queueName): self
    {
        $this->queueName = $queueName;
        return $this;
    }

    public function push(string $payload, int $priority = 100, int $delay = 0, int $retries = 1, string $queue = 'default'): void
    {
        $processAt = null;

        if ($delay > 0) {
            $delayInSeconds = (int)($delay / 1000);
            $processAtTimestamp = time() + $delayInSeconds;
            $processAt = date('Y-m-d H:i:s', $processAtTimestamp);
        }

        $this->queryBuilder->setTable('queue_jobs')->insert([
            'queue' => $queue,
            'payload' => $payload,
            'priority' => $priority,
            'max_retries' => $retries,
            'failed_at' => null,
            'process_at' => $processAt ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'reserved_at' => null
        ]);
    }

    public function pop(string $queue = 'default'): ?array
    {
        $now = date('Y-m-d H:i:s');

        $this->queryBuilder->beginTransaction();

        try {
            $driver = $this->queryBuilder->getConnection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'sqlite') {
                $sql = "UPDATE queue_jobs
                          SET reserved_at = :reserved
                        WHERE id = (
                                SELECT id
                                  FROM queue_jobs
                                 WHERE queue       = :queue
                                   AND (process_at IS NULL OR process_at <= :now)
                                   AND reserved_at IS NULL
                              ORDER BY priority ASC, created_at ASC
                                 LIMIT 1
                              )
                    RETURNING id, payload";

                $stmt = $this->queryBuilder->getConnection()->getPdo()->prepare($sql);
                $stmt->execute([
                    ':reserved' => $now,
                    ':queue' => $queue,
                    ':now' => $now,
                ]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                $stmt->closeCursor();
            } else {
                $id = $this->queryBuilder
                    ->setTable('queue_jobs')
                    ->where('queue', '=', $queue)
                    ->whereRaw('(process_at IS NULL OR process_at <= :now)', ['now' => $now])
                    ->whereNull('reserved_at')
                    ->orderBy('priority', 'ASC')
                    ->orderBy('created_at', 'ASC')
                    ->limit(1)
                    ->lockForUpdate()
                    ->first()['id'] ?? null;

                if ($id) {
                    $this->queryBuilder
                        ->setTable('queue_jobs')
                        ->where('id', '=', $id)
                        ->update(['reserved_at' => $now]);
                    $row = $this->queryBuilder
                        ->setTable('queue_jobs')
                        ->where('id', '=', $id)
                        ->first(['id', 'payload']);
                } else {
                    $row = null;
                }
            }

            $this->queryBuilder->commit();

            return $row ? ['id' => $row['id'], 'payload' => $row['payload']] : null;
        } catch (\Throwable $e) {
            $this->queryBuilder->rollback();
            throw $e;
        }
    }

    public function release(int $jobId, int $delay = 0): void
    {
        $current = $this->queryBuilder->reset()->setTable('queue_jobs')
            ->where('id', '=', $jobId)
            ->first();

        $attempts = ($current['attempts'] ?? 0) + 1;

        $processAt = null;
        if ($delay > 0) {
            $delayInSeconds = (int)($delay / 100);
            $processAtTimestamp = time() + $delayInSeconds;
            $processAt = date('Y-m-d H:i:s', $processAtTimestamp);
        }

        $this->queryBuilder->reset()->setTable('queue_jobs')
            ->where('id', '=', $jobId)
            ->update([
                'reserved_at' => null,
                'process_at' => $processAt,
                'attempts' => $attempts,
            ]);
    }

    public function getNextJobDelay(string $queue = 'default'): ?float
    {
        $job = $this->queryBuilder->reset()
            ->setTable('queue_jobs')
            ->where('queue', '=', $queue)
            ->where('failed_at', 'IS', 'NULL')
            ->whereNull('reserved_at')
            ->orderBy('process_at', 'ASC')
            ->first();

        if (!$job) {
            return null;
        }

        $processAt = $job['process_at'] ?? null;

        if (!$processAt) {
            return 0;
        }

        $now = time();
        $processTimestamp = strtotime($processAt);

        $delay = $processTimestamp - $now;
        return $delay > 0 ? (float)$delay : 0;
    }

    public function count(): int
    {
        return $this->queryBuilder->setTable('queue_jobs')->where('queue', '=', $this->queueName)->count();
    }

    public function clear(): void
    {
        $this->queryBuilder->setTable('queue_jobs')->where('queue', '=', $this->queueName)->delete();
    }

    public function delete(int $jobId): void
    {
        $this->queryBuilder->setTable('queue_jobs')->where('id', '=', $jobId)->delete();
    }

    protected function markJobAsReserved(int $jobId): void
    {
        $this->queryBuilder->setTable('queue_jobs')
            ->where('id', '=', $jobId)
            ->update(['reserved_at' => date('Y-m-d H:i:s')]);
    }

    private function isSqlite(): bool
    {
        return $this->queryBuilder->getConnection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }
}
