<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Queues;

use App\Modules\ForgeEvents\Contracts\QueueInterface;
use App\Modules\ForgeEvents\Enums\QueuePriority;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Traits\FileHelper;

final class FileQueue implements QueueInterface
{
    use FileHelper;

    private string $queuePath;

    public function __construct(string $queueName)
    {
        $this->queuePath = BASE_PATH . "/storage/queues/{$queueName}";
        $this->ensureDirectoryExists($this->queuePath);
    }

    public function push(
        string $payload,
        int $priority = 0,
        int $delayMs = 0,
        int $maxRetries = 3,
        string $queue = 'default'
    ): void {
        $time   = microtime(true) + ($delayMs / 1000);
        $file   = sprintf(
            '%s/%d_%s_%s_%s.job',
            $this->queuePath,
            $priority,
            $queue,
            str_replace('.', '_', (string)$time),
            uniqid()
        );
        $content = serialize([
            'payload' => $payload,
            'attempts' => 0,
            'queue'   => $queue,
        ]);
        file_put_contents($file, $content, LOCK_EX);
    }

    public function pop(string $queue = 'default'): ?array
    {
        $pattern = "{$this->queuePath}/*_{$queue}_*.job";
        $files   = glob($pattern);

        if (empty($files)) {
            return null;
        }

        usort($files, function ($a, $b) {
            $priorityA = (int)substr(basename($a), 0, strpos(basename($a), '_'));
            $priorityB = (int)substr(basename($b), 0, strpos(basename($b), '_'));

            if ($priorityA === $priorityB) {
                $timeA = (int)substr(basename($a), strrpos(basename($a), '_') + 1, -4);
                $timeB = (int)substr(basename($b), strrpos(basename($b), '_') + 1, -4);
                return $timeA <=> $timeB;
            }

            return $priorityA <=> $priorityB;
        });

        if (!empty($files)) {
            FileExistenceCache::preload($files);
        }

        foreach ($files as $file) {
            if (!FileExistenceCache::exists($file)) {
                continue;
            }

            $handle = @fopen($file, 'r+');
            if (!$handle) {
                continue;
            }

            if (flock($handle, LOCK_EX | LOCK_NB)) {
                $content = stream_get_contents($handle);

                $jobData = @unserialize($content);

                if ($jobData === false || (isset($jobData['processAfter']) && $jobData['processAfter'] > microtime(true))) {
                    flock($handle, LOCK_UN);
                    fclose($handle);
                    if ($jobData === false) {
                        error_log("Failed to unserialize job file: " . $file);
                        @unlink($file);
                    }
                    continue;
                }

                $originalPayload = $jobData['payload'] ?? null;
                $attempts = $jobData['attempts'] ?? 0;
                $jobId = null;

                if ($originalPayload === null) {
                    error_log("Job file {$file} has no payload.");
                    flock($handle, LOCK_UN);
                    fclose($handle);
                    @unlink($file);
                    continue;
                }

                @unlink($file);

                flock($handle, LOCK_UN);
                fclose($handle);

                $processedPayload = unserialize($originalPayload);
                $processedPayload['attempts'] = $attempts;
                $processedPayload['jobId'] = $jobId;


                return ['id' => $jobId, 'payload' => serialize($processedPayload)];
            } else {
                fclose($handle);
                continue;
            }
        }

        return null;
    }

    public function count(): int
    {
        $files = glob("{$this->queuePath}/*.job");
        if (!empty($files)) {
            FileExistenceCache::preload($files);
        }
        return count($files);
    }

    public function clear(): void
    {
        $files = glob("{$this->queuePath}/*.job");
        if (!empty($files)) {
            FileExistenceCache::preload($files);
        }
        foreach ($files as $file) {
            if (FileExistenceCache::exists($file)) {
                unlink($file);
            }
        }
    }

    public function release(int $jobId, int $delay = 0): void
    {
    }

    public function getNextJobDelay(string $queue = 'default'): ?float
    {
        return 0;
    }
}
