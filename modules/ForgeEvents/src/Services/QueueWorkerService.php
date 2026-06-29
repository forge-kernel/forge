<?php

declare(strict_types=1);

namespace Modules\ForgeEvents\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Traits\FileHelper;

#[Service]
final class QueueWorkerService
{
    use FileHelper;

    private const STORAGE_FILE = 'storage/framework/queue-workers.json';
    private const OUTPUT_DIR = 'storage/framework/queue-worker-outputs';

    public function getWorkers(): array
    {
        $filePath = BASE_PATH . '/' . self::STORAGE_FILE;

        if (!FileExistenceCache::exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['queue_workers'])) {
            return [];
        }

        $workers = [];
        foreach ($data['queue_workers'] as $worker) {
            if (is_array($worker)) {
                $pids = $worker['pids'] ?? [];
                $isRunning = false;
                if (!empty($pids)) {
                    foreach ($pids as $pid) {
                        if ($this->isPidRunning($pid)) {
                            $isRunning = true;
                            break;
                        }
                    }
                }
                if (!$isRunning && !empty($pids)) {
                    $worker['pids'] = [];
                    $worker['status'] = 'stopped';
                    $worker['updated_at'] = date('Y-m-d H:i:s');
                    $this->saveWorker($worker, $worker['id'] ?? '');
                }
                $worker['is_running'] = $isRunning;
                $workers[] = $worker;
            }
        }

        return $workers;
    }

    public function getWorker(string $id): ?array
    {
        $filePath = BASE_PATH . '/' . self::STORAGE_FILE;

        if (!FileExistenceCache::exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['queue_workers'][$id])) {
            return null;
        }

        $worker = $data['queue_workers'][$id];
        if (!is_array($worker)) {
            return null;
        }

        $pids = $worker['pids'] ?? [];
        $isRunning = false;
        if (!empty($pids)) {
            foreach ($pids as $pid) {
                if ($this->isPidRunning($pid)) {
                    $isRunning = true;
                    break;
                }
            }
        }
        if (!$isRunning && !empty($pids)) {
            $worker['pids'] = [];
            $worker['status'] = 'stopped';
            $worker['updated_at'] = date('Y-m-d H:i:s');
            $this->saveWorker($worker, $id);
        }
        $worker['is_running'] = $isRunning;

        return $worker;
    }

    public function createWorker(string $name, array $queues, int $processes): array
    {
        $id = uniqid('worker_', true);
        $now = date('Y-m-d H:i:s');

        $worker = [
            'id' => $id,
            'name' => $name,
            'queues' => $queues,
            'processes' => max(1, min(10, $processes)),
            'pids' => [],
            'status' => 'stopped',
            'created_at' => $now,
            'updated_at' => $now,
            'last_started_at' => null,
            'output_file' => $this->getOutputFilePath($id),
        ];

        $this->saveWorker($worker);

        return $worker;
    }

    public function updateWorker(string $id, array $data): ?array
    {
        $worker = $this->getWorker($id);
        if (!$worker) {
            return null;
        }

        if (isset($data['name'])) {
            $worker['name'] = trim($data['name']);
        }

        if (isset($data['queues']) && is_array($data['queues'])) {
            $worker['queues'] = $data['queues'];
        }

        if (isset($data['processes'])) {
            $worker['processes'] = max(1, min(10, (int) $data['processes']));
        }

        $worker['updated_at'] = date('Y-m-d H:i:s');

        $this->saveWorker($worker, $id);

        return $worker;
    }

    public function removeWorker(string $id): bool
    {
        $filePath = BASE_PATH . '/' . self::STORAGE_FILE;

        if (!FileExistenceCache::exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['queue_workers'][$id])) {
            return false;
        }

        $worker = $data['queue_workers'][$id];
        if (!is_array($worker)) {
            return false;
        }

        $pids = $worker['pids'] ?? [];
        if (!empty($pids)) {
            foreach ($pids as $pid) {
                if ($this->isPidRunning($pid)) {
                    if (function_exists('posix_kill')) {
                        posix_kill($pid, defined('SIGTERM') ? SIGTERM : 15);
                    } else {
                        @exec("kill -TERM {$pid} 2>/dev/null");
                    }
                }
            }

            sleep(2);

            foreach ($pids as $pid) {
                if ($this->isPidRunning($pid)) {
                    if (function_exists('posix_kill')) {
                        posix_kill($pid, defined('SIGKILL') ? SIGKILL : 9);
                    } else {
                        @exec("kill -KILL {$pid} 2>/dev/null");
                    }
                }
            }
        }

        $filePath = BASE_PATH . '/' . self::STORAGE_FILE;

        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['queue_workers'])) {
            return false;
        }

        if (isset($data['queue_workers'][$id])) {
            unset($data['queue_workers'][$id]);
            $this->ensureDirectoryExists(dirname($filePath));
            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

            $outputFile = $this->getOutputFilePath($id);
            if (FileExistenceCache::exists($outputFile)) {
                @unlink($outputFile);
            }

            return true;
        }

        return false;
    }

    public function startWorker(string $id): bool
    {
        $filePath = BASE_PATH . '/' . self::STORAGE_FILE;

        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['queue_workers'][$id])) {
            return false;
        }

        $worker = $data['queue_workers'][$id];
        if (!is_array($worker)) {
            return false;
        }

        $pids = $worker['pids'] ?? [];
        $isRunning = false;
        if (!empty($pids)) {
            foreach ($pids as $pid) {
                if ($this->isPidRunning($pid)) {
                    $isRunning = true;
                    break;
                }
            }
        }

        if ($isRunning) {
            return false;
        }

        $outputFile = $this->getOutputFilePath($id);
        $this->ensureDirectoryExists(dirname($outputFile));

        $phpExecutable = $this->getPhpExecutable();
        $forgePath = BASE_PATH . '/forge.php';
        $queues = implode(',', $worker['queues'] ?? []);
        $processes = $worker['processes'] ?? 1;

        $command = sprintf(
            '(cd %s && nohup %s %s modules:queue:work --workers=%d --queues=%s >> %s 2>&1) > /dev/null 2>&1 & echo $!',
            escapeshellarg(BASE_PATH),
            escapeshellarg($phpExecutable),
            escapeshellarg($forgePath),
            (int) $processes,
            escapeshellarg($queues),
            escapeshellarg($outputFile)
        );

        $pidOutput = [];
        $returnCode = 0;
        @exec($command, $pidOutput, $returnCode);

        $pid = null;
        if (!empty($pidOutput[0]) && is_numeric($pidOutput[0])) {
            $pid = (int) $pidOutput[0];
        }

        if ($pid) {
            $worker['pids'] = [$pid];
            $worker['status'] = 'running';
            $worker['last_started_at'] = date('Y-m-d H:i:s');
            $worker['updated_at'] = date('Y-m-d H:i:s');

            $this->saveWorker($worker, $id);
            $this->appendOutput($id, "\n=== Worker started at " . date('Y-m-d H:i:s') . " (PID: {$pid}) ===\n");

            return true;
        }

        return false;
    }

    public function stopWorker(string $id): bool
    {
        $filePath = BASE_PATH . '/' . self::STORAGE_FILE;

        if (!FileExistenceCache::exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['queue_workers'][$id])) {
            return false;
        }

        $worker = $data['queue_workers'][$id];
        if (!is_array($worker)) {
            return false;
        }

        $pids = $worker['pids'] ?? [];
        if (empty($pids)) {
            return false;
        }

        $stopped = false;
        foreach ($pids as $pid) {
            if ($this->isPidRunning($pid)) {
                if (function_exists('posix_kill')) {
                    posix_kill($pid, defined('SIGTERM') ? SIGTERM : 15);
                } else {
                    @exec("kill -TERM {$pid} 2>/dev/null");
                }
                $stopped = true;
            }
        }

        if ($stopped) {
            sleep(2);

            foreach ($pids as $pid) {
                if ($this->isPidRunning($pid)) {
                    if (function_exists('posix_kill')) {
                        posix_kill($pid, defined('SIGKILL') ? SIGKILL : 9);
                    } else {
                        @exec("kill -KILL {$pid} 2>/dev/null");
                    }
                }
            }

            $worker['pids'] = [];
            $worker['status'] = 'stopped';
            $worker['updated_at'] = date('Y-m-d H:i:s');

            $this->saveWorker($worker, $id);
            $this->appendOutput($id, "\n=== Worker stopped at " . date('Y-m-d H:i:s') . " ===\n");

            return true;
        }

        return false;
    }

    public function isWorkerRunning(string $id): bool
    {
        $filePath = BASE_PATH . '/' . self::STORAGE_FILE;

        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['queue_workers'][$id])) {
            return false;
        }

        $worker = $data['queue_workers'][$id];
        if (!is_array($worker)) {
            return false;
        }

        $pids = $worker['pids'] ?? [];
        if (empty($pids)) {
            return false;
        }

        foreach ($pids as $pid) {
            if ($this->isPidRunning($pid)) {
                return true;
            }
        }

        $worker['pids'] = [];
        $worker['status'] = 'stopped';
        $worker['updated_at'] = date('Y-m-d H:i:s');
        $this->saveWorker($worker, $id);

        return false;
    }

    private function isPidRunning(int $pid): bool
    {
        if (function_exists('posix_kill')) {
            return posix_kill($pid, 0);
        }

        $output = [];
        $returnCode = 0;
        @exec("ps -p {$pid} 2>/dev/null", $output, $returnCode);
        return $returnCode === 0;
    }

    public function getOutputFilePath(string $id): string
    {
        $outputDir = BASE_PATH . '/' . self::OUTPUT_DIR;
        $this->ensureDirectoryExists($outputDir);
        return $outputDir . '/' . $id . '.log';
    }

    public function appendOutput(string $id, string $content): void
    {
        $outputFile = $this->getOutputFilePath($id);
        $this->ensureDirectoryExists(dirname($outputFile));
        file_put_contents($outputFile, $content, FILE_APPEND | LOCK_EX);
    }

    public function getLastOutput(string $id, int $lines = 200): ?string
    {
        $outputFile = $this->getOutputFilePath($id);

        if (!file_exists($outputFile) || @filesize($outputFile) === 0) {
            return null;
        }

        $content = @file_get_contents($outputFile);
        if ($content === false) {
            return null;
        }

        $allLines = explode("\n", $content);
        $lastLines = array_slice($allLines, -$lines);

        return implode("\n", $lastLines);
    }

    public function clearOutput(string $id): bool
    {
        $outputFile = $this->getOutputFilePath($id);

        if (file_exists($outputFile)) {
            return @file_put_contents($outputFile, '') !== false;
        }

        return true;
    }

    public function getOutputFileSize(string $id): int
    {
        $outputFile = $this->getOutputFilePath($id);

        if (file_exists($outputFile)) {
            return (int)@filesize($outputFile);
        }

        return 0;
    }

    public function getPhpExecutable(): string
    {
        static $phpPath = null;

        if ($phpPath !== null) {
            return $phpPath;
        }

        $possiblePaths = [];

        $whichOutput = [];
        $whichReturnCode = 0;
        @exec('which php 2>/dev/null', $whichOutput, $whichReturnCode);
        if ($whichReturnCode === 0 && !empty($whichOutput[0])) {
            $whichPath = trim($whichOutput[0]);
            if ($whichPath && !str_contains($whichPath, 'fpm') && file_exists($whichPath)) {
                $possiblePaths[] = $whichPath;
            }
        }

        if (defined('PHP_BINARY') && PHP_BINARY) {
            $phpBinary = PHP_BINARY;
            if (!str_contains(strtolower($phpBinary), 'fpm') && file_exists($phpBinary)) {
                $possiblePaths[] = $phpBinary;
            }
        }

        $commonPaths = ['/usr/bin/php', '/usr/local/bin/php', '/opt/homebrew/bin/php'];
        foreach ($commonPaths as $path) {
            if (file_exists($path) && is_executable($path) && !str_contains($path, 'fpm')) {
                $possiblePaths[] = $path;
            }
        }

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path) && is_executable($path)) {
                if (str_contains(strtolower($path), 'fpm')) {
                    continue;
                }

                $testOutput = [];
                $testReturnCode = 0;
                @exec(escapeshellarg($path) . ' -v 2>/dev/null', $testOutput, $testReturnCode);
                if ($testReturnCode === 0 && !empty($testOutput[0])) {
                    if (str_contains($testOutput[0], 'cli')) {
                        $phpPath = $path;
                        return $phpPath;
                    }
                }
            }
        }

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path) && is_executable($path)) {
                if (str_contains(strtolower($path), 'fpm')) {
                    continue;
                }
                $phpPath = $path;
                return $phpPath;
            }
        }

        error_log('QueueWorkerService: Could not find PHP executable, falling back to "php" command');
        $phpPath = 'php';
        return $phpPath;
    }

    public function getPhpInfo(): array
    {
        static $phpInfo = null;

        if ($phpInfo !== null) {
            return $phpInfo;
        }

        $phpPath = $this->getPhpExecutable();
        $version = PHP_VERSION;
        $versionString = '';

        if ($phpPath !== 'php' && file_exists($phpPath)) {
            $versionOutput = [];
            @exec(escapeshellarg($phpPath) . ' -v 2>/dev/null', $versionOutput, $returnCode);
            if ($returnCode === 0 && !empty($versionOutput[0])) {
                if (preg_match('/PHP\s+([\d.]+)/', $versionOutput[0], $matches)) {
                    $versionString = $matches[1];
                }
            }
        } else {
            $versionOutput = [];
            @exec('php -v 2>/dev/null', $versionOutput, $returnCode);
            if ($returnCode === 0 && !empty($versionOutput[0])) {
                if (preg_match('/PHP\s+([\d.]+)/', $versionOutput[0], $matches)) {
                    $versionString = $matches[1];
                }
            }
        }

        $phpInfo = [
            'path' => $phpPath,
            'version' => $versionString ?: $version,
            'is_default' => $phpPath === 'php',
        ];

        return $phpInfo;
    }

    private function normalizeCommand(array $worker): string
    {
        $basePath = BASE_PATH;
        $phpExecutable = $this->getPhpExecutable();
        $forgePath = $basePath . '/forge.php';
        $queues = implode(',', $worker['queues'] ?? []);
        $processes = $worker['processes'] ?? 1;

        $command = "cd " . escapeshellarg($basePath) . " && " . escapeshellarg($phpExecutable) . " " . escapeshellarg($forgePath) . " modules:queue:work --workers=" . (int) $processes . " --queues=" . escapeshellarg($queues);

        return $command;
    }

    private function saveWorker(array $worker, ?string $id = null): void
    {
        $filePath = BASE_PATH . '/' . self::STORAGE_FILE;
        $this->ensureDirectoryExists(dirname($filePath));

        $data = [];
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }

        if (!isset($data['queue_workers'])) {
            $data['queue_workers'] = [];
        }

        $workerId = $id ?? $worker['id'] ?? '';
        if ($workerId) {
            $data['queue_workers'][$workerId] = $worker;
        }

        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
}
