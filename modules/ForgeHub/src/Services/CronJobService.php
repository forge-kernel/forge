<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Traits\FileHelper;

#[Service]
final class CronJobService
{
    use FileHelper;

    private const STORAGE_FILE = 'storage/framework/cron-jobs.json';
    private const OUTPUT_DIR = 'storage/framework/cron-outputs';

    private array $jobCache = [];
    private int $cacheTime = 0;
    private int $cacheTtl = 300; // 5 minutes
    private array $pendingCrontabOperations = [];
    private int $lastCrontabUpdate = 0;
    private int $crontabUpdateDelay = 30; // 30 seconds delay for batching

    public function createCronJob(string $name, string $command, array $schedule, bool $advanced = false, string $commandType = 'forge'): array
    {
        $id = uniqid('cron_', true);
        $now = date('Y-m-d H:i:s');

        if ($advanced) {
            $cronExpression = $schedule['expression'] ?? '* * * * *';
        } else {
            $cronExpression = $this->convertSimpleToCron($schedule);
        }

        $outputFile = $this->getOutputFilePath($id);
        $normalizedCommand = $this->normalizeCommand($command, $commandType, $outputFile);

        $cronJob = [
            'id' => $id,
            'name' => $name,
            'command' => $command,
            'command_type' => $commandType,
            'normalized_command' => $normalizedCommand,
            'schedule' => $schedule,
            'cron_expression' => $cronExpression,
            'enabled' => true,
            'created_at' => $now,
            'updated_at' => $now,
            'last_run' => null,
            'last_output' => null,
            'output_file' => $outputFile,
        ];

        $jobs = $this->getCronJobs();
        $jobs[] = $cronJob;
        $this->saveCronJobsToFile($jobs);
        $this->queueCrontabUpdate('create', ['job' => $cronJob]);

        return $cronJob;
    }

    public function convertSimpleToCron(array $schedule): string
    {
        $minute = $this->normalizeScheduleValue($schedule['minutes'] ?? '');
        $hour = $this->normalizeScheduleValue($schedule['hours'] ?? '');
        $day = $this->normalizeScheduleValue($schedule['days'] ?? '*');
        $month = $this->normalizeScheduleValue($schedule['months'] ?? '*');
        $weekday = '*';

        return sprintf('%s %s %s %s %s', $minute, $hour, $day, $month, $weekday);
    }

    private function normalizeScheduleValue(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '*') {
            return '*';
        }

        if (is_numeric($value)) {
            return (string)(int)$value;
        }

        return $value;
    }

    public function getOutputFilePath(string $jobId): string
    {
        $this->ensureDirectoryExists(BASE_PATH . '/' . self::OUTPUT_DIR);
        return BASE_PATH . '/' . self::OUTPUT_DIR . '/' . $jobId . '.log';
    }

    public function normalizeCommand(string $command, string $type, ?string $outputFile = null): string
    {
        $basePath = BASE_PATH;
        $command = trim($command);
        $phpExecutable = $this->getPhpExecutable();

        if ($phpExecutable === 'php') {
            error_log('CronJobService: Warning - Using "php" command which may not be in PATH. Consider setting PHP_BINARY or ensuring php is in PATH.');
        }

        if ($type === 'script') {
            if (str_starts_with($command, '/')) {
                $scriptPath = $command;
            } else {
                $scriptPath = $basePath . '/' . ltrim($command, '/');
            }

            if (!file_exists($scriptPath)) {
                $baseCmd = escapeshellarg($phpExecutable) . " " . escapeshellarg($scriptPath);
            } else {
                $baseCmd = "cd " . escapeshellarg($basePath) . " && " . escapeshellarg($phpExecutable) . " " . escapeshellarg($scriptPath);
            }
        } else {
            if (str_starts_with($command, 'php forge.php')) {
                $forgeCommand = trim(str_replace('php forge.php', '', $command));
            } else {
                $forgeCommand = trim($command);
            }

            $forgePath = $basePath . '/forge.php';
            $baseCmd = "cd " . escapeshellarg($basePath) . " && " . escapeshellarg($phpExecutable) . " " . escapeshellarg($forgePath) . " " . escapeshellcmd($forgeCommand);
        }

        if ($outputFile) {
            $this->ensureDirectoryExists(dirname($outputFile));
            return $baseCmd . " >> " . escapeshellarg($outputFile) . " 2>&1";
        }

        return $baseCmd;
    }

    private function getPhpExecutable(): string
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

        error_log('CronJobService: Could not find PHP executable, falling back to "php" command');
        $phpPath = 'php';
        return $phpPath;
    }

    public function getCronJobs(): array
    {
        if ($this->isCacheValid()) {
            return $this->jobCache;
        }

        $jobs = $this->loadCronJobsFromFile();
        $this->jobCache = $jobs;
        $this->cacheTime = time();

        return $jobs;
    }

    private function isCacheValid(): bool
    {
        return $this->cacheTime > 0 && (time() - $this->cacheTime) < $this->cacheTtl;
    }

    private function loadCronJobsFromFile(): array
    {
        $filePath = BASE_PATH . '/' . self::STORAGE_FILE;

        $filePathExists = FileExistenceCache::exists($filePath);

        if (!$filePathExists) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['cron_jobs'])) {
            return [];
        }

        $jobs = [];
        foreach ($data['cron_jobs'] as $job) {
            if (is_array($job)) {
                $jobs[] = $job;
            }
        }

        return $jobs;
    }

    private function saveCronJobsToFile(array $jobs): bool
    {
        $filePath = BASE_PATH . '/' . self::STORAGE_FILE;
        $this->ensureDirectoryExists(dirname($filePath));

        $data = ['cron_jobs' => []];
        foreach ($jobs as $job) {
            if (isset($job['id'])) {
                $data['cron_jobs'][$job['id']] = $job;
            }
        }

        $result = file_put_contents(
            $filePath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );

        if ($result !== false) {
            $this->jobCache = $jobs;
            $this->cacheTime = time();
            return true;
        }

        return false;
    }

    private function queueCrontabUpdate(string $operation, array $data = []): void
    {
        $this->pendingCrontabOperations[] = ['operation' => $operation, 'data' => $data, 'timestamp' => time()];

        if (time() - $this->lastCrontabUpdate >= $this->crontabUpdateDelay) {
            $this->processPendingCrontabOperations();
        }
    }

    private function processPendingCrontabOperations(): void
    {
        if (empty($this->pendingCrontabOperations)) {
            return;
        }

        $this->rebuildCrontab();
        $this->pendingCrontabOperations = [];
        $this->lastCrontabUpdate = time();
    }

    public function rebuildCrontab(): bool
    {
        // Process any pending operations first
        $this->processPendingCrontabOperations();

        $output = [];
        $returnCode = 0;
        @exec('crontab -l 2>/dev/null', $output, $returnCode);

        $existingCrontab = [];
        if ($returnCode === 0) {
            $existingCrontab = $output;
        }

        $marker = '# ForgeHub Cron Jobs';
        $endMarker = '# End ForgeHub Cron Jobs';

        $newCrontab = [];
        $inForgeHubSection = false;

        foreach ($existingCrontab as $line) {
            $trimmed = trim($line);

            if ($trimmed === $marker) {
                if ($inForgeHubSection) {
                    continue;
                }
                $inForgeHubSection = true;
                continue;
            }

            if ($inForgeHubSection) {
                if ($trimmed === $endMarker) {
                    $inForgeHubSection = false;
                    continue;
                }
                continue;
            }

            $newCrontab[] = $line;
        }

        $allJobs = $this->getCronJobs();
        $enabledJobs = array_filter($allJobs, fn($job) => $job['enabled'] ?? true);

        if (!empty($enabledJobs)) {
            $newCrontab[] = '';
            $newCrontab[] = $marker;
            foreach ($enabledJobs as $job) {
                $cronExpression = $job['cron_expression'] ?? '* * * * *';
                $command = $job['normalized_command'] ?? '';
                $newCrontab[] = "# Job ID: {$job['id']}";
                $newCrontab[] = "{$cronExpression} {$command}";
            }
            $newCrontab[] = $endMarker;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'crontab_');
        if ($tempFile === false) {
            return false;
        }

        file_put_contents($tempFile, implode("\n", $newCrontab) . "\n");
        @exec("crontab " . escapeshellarg($tempFile) . " 2>/dev/null", $execOutput, $execReturnCode);
        @unlink($tempFile);

        if ($execReturnCode !== 0) {
            error_log('CronJobService: Failed to install crontab entry. Jobs are stored but not automatically installed. You may need to manually install them.');
        }

        return $execReturnCode === 0;
    }

    public function updateCronJob(string $id, array $data): ?array
    {
        $job = $this->getCronJob($id);
        if (!$job) {
            return null;
        }

        $updated = array_merge($job, $data);
        $updated['updated_at'] = date('Y-m-d H:i:s');

        if (isset($data['schedule'])) {
            $advanced = isset($data['schedule']['mode']) && $data['schedule']['mode'] === 'advanced';
            if ($advanced) {
                $updated['cron_expression'] = $data['schedule']['expression'] ?? '* * * * *';
            } else {
                $updated['cron_expression'] = $this->convertSimpleToCron($data['schedule']);
            }
        }

        if (isset($data['command']) && isset($data['command_type'])) {
            $outputFile = $this->getOutputFilePath($id);
            $updated['normalized_command'] = $this->normalizeCommand($data['command'], $data['command_type'], $outputFile);
            $updated['output_file'] = $outputFile;
        }

        $jobs = $this->getCronJobs();
        $updatedJobs = [];
        foreach ($jobs as $job) {
            if ($job['id'] === $id) {
                $updatedJobs[] = $updated;
            } else {
                $updatedJobs[] = $job;
            }
        }
        $this->saveCronJobsToFile($updatedJobs);
        $this->queueCrontabUpdate('update', ['job' => $updated]);

        return $updated;
    }

    public function getCronJob(string $id): ?array
    {
        $jobs = $this->getCronJobs();

        foreach ($jobs as $job) {
            if (isset($job['id']) && $job['id'] === $id) {
                return $job;
            }
        }

        return null;
    }

    public function deleteCronJob(string $id): bool
    {
        $jobs = $this->getCronJobs();
        $jobToDelete = null;
        $updatedJobs = [];

        foreach ($jobs as $job) {
            if ($job['id'] === $id) {
                $jobToDelete = $job;
            } else {
                $updatedJobs[] = $job;
            }
        }

        if ($jobToDelete === null) {
            return false;
        }

        $this->saveCronJobsToFile($updatedJobs);
        $this->queueCrontabUpdate('delete', ['job' => $jobToDelete]);

        return true;
    }

    public function validateCronExpression(string $expression): bool
    {
        $parts = explode(' ', trim($expression));
        if (count($parts) !== 5) {
            return false;
        }

        foreach ($parts as $index => $part) {
            if ($part === '*') {
                continue;
            }

            $ranges = [
                [0, 59],
                [0, 23],
                [1, 31],
                [1, 12],
                [0, 6],
            ];

            if (preg_match('/^(\d+)-(\d+)$/', $part, $matches)) {
                $start = (int)$matches[1];
                $end = (int)$matches[2];
                if ($start < $ranges[$index][0] || $end > $ranges[$index][1] || $start > $end) {
                    return false;
                }
            } elseif (preg_match('/^\*\/(\d+)$/', $part, $matches)) {
                $step = (int)$matches[1];
                if ($step < 1 || $step > $ranges[$index][1]) {
                    return false;
                }
            } elseif (preg_match('/^(\d+)$/', $part)) {
                $value = (int)$part;
                if ($value < $ranges[$index][0] || $value > $ranges[$index][1]) {
                    return false;
                }
            } elseif (preg_match('/^(\d+),(\d+)(?:,(\d+))*$/', $part)) {
                $values = explode(',', $part);
                foreach ($values as $value) {
                    $intValue = (int)$value;
                    if ($intValue < $ranges[$index][0] || $intValue > $ranges[$index][1]) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }

        return true;
    }

    public function getHumanReadableSchedule(string $cronExpression): string
    {
        $parts = explode(' ', trim($cronExpression));
        if (count($parts) !== 5) {
            return 'Invalid schedule';
        }

        [$minute, $hour, $day, $month, $weekday] = $parts;

        if ($minute === '*' && $hour === '*' && $day === '*' && $month === '*' && $weekday === '*') {
            return 'Every minute';
        }

        if ($minute !== '*' && $hour === '*' && $day === '*' && $month === '*' && $weekday === '*') {
            if (preg_match('/^\*\/(\d+)$/', $minute, $matches)) {
                return "Every {$matches[1]} minutes";
            }
            return "Every minute at {$minute} seconds past";
        }

        if ($minute !== '*' && $hour !== '*' && $day === '*' && $month === '*' && $weekday === '*') {
            $hour12 = (int)$hour > 12 ? (int)$hour - 12 : (int)$hour;
            $ampm = (int)$hour >= 12 ? 'PM' : 'AM';
            if ($hour12 === 0) {
                $hour12 = 12;
            }
            return "Daily at {$hour12}:{$minute} {$ampm}";
        }

        if ($day !== '*' && $month === '*') {
            return "Monthly on day {$day}";
        }

        if ($month !== '*') {
            $monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $monthName = isset($monthNames[(int)$month]) ? $monthNames[(int)$month] : $month;
            return "Yearly in {$monthName}";
        }

        return $cronExpression;
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

    public function getExecutableCommand(array $cronJob): string
    {
        $commandType = $cronJob['command_type'] ?? 'forge';
        $outputFile = $this->getOutputFilePath($cronJob['id'] ?? '');
        $phpExecutable = $this->getPhpExecutable();

        if (isset($cronJob['normalized_command'])) {
            $baseCommand = $cronJob['normalized_command'];

            if ($phpExecutable !== 'php') {
                $escapedPhp = escapeshellarg($phpExecutable);
                $baseCommand = preg_replace(
                    '/(^|\s+)php(\s+|$)/',
                    '$1' . $escapedPhp . '$2',
                    $baseCommand
                );
            }

            if (str_contains($baseCommand, '>>')) {
                return $baseCommand;
            }
            return $baseCommand . " >> " . escapeshellarg($outputFile) . " 2>&1";
        }

        return $this->normalizeCommand($cronJob['command'] ?? '', $commandType, $outputFile);
    }

    public function getLastOutput(string $jobId, int $maxLines = 100): ?string
    {
        $outputFile = $this->getOutputFilePath($jobId);

        if (!file_exists($outputFile)) {
            return null;
        }

        $fileSize = filesize($outputFile);
        if ($fileSize === false || $fileSize === 0) {
            return null;
        }

        // For large files, read from the end more efficiently
        if ($fileSize > 1024 * 1024) { // 1MB threshold
            return $this->readLastLinesFromFile($outputFile, $maxLines);
        }

        $handle = fopen($outputFile, 'r');
        if ($handle === false) {
            return null;
        }

        $lines = [];
        $buffer = '';

        // Use stream reading for better memory efficiency
        while (!feof($handle)) {
            $buffer .= fgets($handle, 8192);

            // Process complete lines
            while (($newlinePos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newlinePos);
                $buffer = substr($buffer, $newlinePos + 1);

                if (trim($line) !== '') {
                    $lines[] = $line;
                }
            }
        }

        // Process any remaining buffer
        if (trim($buffer) !== '') {
            $lines[] = $buffer;
        }

        fclose($handle);

        if (empty($lines)) {
            return null;
        }

        if (count($lines) <= $maxLines) {
            return implode("\n", $lines);
        }

        return implode("\n", array_slice($lines, -$maxLines));
    }

    private function readLastLinesFromFile(string $filePath, int $maxLines): ?string
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return null;
        }

        // Seek to end of file
        fseek($handle, 0, SEEK_END);
        $fileSize = ftell($handle);
        $position = $fileSize - 1;
        $lines = [];
        $currentLine = '';

        // Read backwards to get last lines efficiently
        while ($position >= 0 && count($lines) < $maxLines) {
            fseek($handle, $position);
            $char = fgetc($handle);

            if ($char === "\n") {
                if (!empty($currentLine)) {
                    $lines[] = strrev($currentLine);
                    $currentLine = '';
                }
            } else {
                $currentLine .= $char;
            }

            $position--;
        }

        // Add the first line if we didn't hit a newline
        if (!empty($currentLine)) {
            $lines[] = strrev($currentLine);
        }

        fclose($handle);

        if (empty($lines)) {
            return null;
        }

        return implode("\n", array_reverse($lines));
    }

    public function appendOutput(string $jobId, string $output): void
    {
        $outputFile = $this->getOutputFilePath($jobId);
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] " . trim($output) . "\n";

        // Use atomic file writing with retry mechanism
        $maxRetries = 3;
        $retryDelay = 100000; // 100ms in microseconds

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $result = file_put_contents($outputFile, $logEntry, FILE_APPEND | LOCK_EX);

            if ($result !== false) {
                return;
            }

            // Brief delay before retry
            if ($attempt < $maxRetries - 1) {
                usleep($retryDelay);
                $retryDelay *= 2; // Exponential backoff
            }
        }

        // If all retries failed, log error
        error_log("CronJobService: Failed to write to output file {$outputFile} after {$maxRetries} attempts");
    }

    public function clearOutput(string $jobId): bool
    {
        $outputFile = $this->getOutputFilePath($jobId);
        if (file_exists($outputFile)) {
            return unlink($outputFile);
        }
        return true;
    }

    public function getOutputFileSize(string $jobId): int
    {
        $outputFile = $this->getOutputFilePath($jobId);
        if (file_exists($outputFile)) {
            return filesize($outputFile);
        }
        return 0;
    }

    public function __destruct()
    {
        // Process any pending crontab operations before service destruction
        $this->processPendingCrontabOperations();
    }

    public function clearCache(): void
    {
        $this->invalidateCache();
    }

    private function invalidateCache(): void
    {
        $this->jobCache = [];
        $this->cacheTime = 0;
    }

    public function getCacheStats(): array
    {
        return [
            'cache_valid' => $this->isCacheValid(),
            'cache_time' => $this->cacheTime,
            'cache_age' => $this->cacheTime > 0 ? time() - $this->cacheTime : null,
            'pending_crontab_operations' => count($this->pendingCrontabOperations),
            'last_crontab_update' => $this->lastCrontabUpdate,
        ];
    }

    private function getCrontabEntries(): array
    {
        $output = [];
        $returnCode = 0;
        @exec('crontab -l 2>/dev/null', $output, $returnCode);

        if ($returnCode !== 0) {
            return [];
        }

        $entries = [];
        $marker = '# ForgeHub Cron Jobs';
        $inForgeHubSection = false;
        $currentId = null;

        foreach ($output as $line) {
            $line = trim($line);

            if ($line === $marker) {
                $inForgeHubSection = true;
                continue;
            }

            if ($inForgeHubSection) {
                if (preg_match('/^# Job ID: (.+)$/', $line, $matches)) {
                    $currentId = $matches[1];
                } elseif (preg_match('/^# End ForgeHub Cron Jobs$/', $line)) {
                    break;
                } elseif ($currentId && !empty($line) && !str_starts_with($line, '#')) {
                    $entries[$currentId] = $line;
                    $currentId = null;
                }
            }
        }

        return $entries;
    }

    private function installCrontabEntry(array $cronJob): bool
    {
        if (!($cronJob['enabled'] ?? true)) {
            $this->removeCrontabEntry($cronJob);
            return true;
        }

        $output = [];
        $returnCode = 0;
        @exec('crontab -l 2>/dev/null', $output, $returnCode);

        $existingCrontab = [];
        if ($returnCode === 0) {
            $existingCrontab = $output;
        }

        $marker = '# ForgeHub Cron Jobs';
        $endMarker = '# End ForgeHub Cron Jobs';
        $jobId = $cronJob['id'];
        $cronExpression = $cronJob['cron_expression'] ?? '* * * * *';
        $command = $cronJob['normalized_command'] ?? '';

        $newCrontab = [];
        $inForgeHubSection = false;
        $allJobs = [];
        $currentJobId = null;

        foreach ($existingCrontab as $line) {
            $trimmed = trim($line);

            if ($trimmed === $marker) {
                if ($inForgeHubSection) {
                    continue;
                }
                $inForgeHubSection = true;
                continue;
            }

            if ($inForgeHubSection) {
                if (preg_match('/^# Job ID: (.+)$/', $trimmed, $matches)) {
                    $currentJobId = $matches[1];
                    continue;
                } elseif ($trimmed === $endMarker) {
                    $inForgeHubSection = false;
                    continue;
                } elseif ($currentJobId && !str_starts_with($trimmed, '#')) {
                    if ($currentJobId !== $jobId) {
                        $allJobs[] = [
                            'id' => $currentJobId,
                            'command' => $trimmed,
                        ];
                    }
                    $currentJobId = null;
                    continue;
                }
                continue;
            }

            $newCrontab[] = $line;
        }

        $allJobs[] = [
            'id' => $jobId,
            'command' => "{$cronExpression} {$command}",
        ];

        $newCrontab[] = '';
        $newCrontab[] = $marker;
        foreach ($allJobs as $job) {
            $jobData = $this->getCronJob($job['id']);
            if ($jobData && ($jobData['enabled'] ?? true)) {
                $newCrontab[] = "# Job ID: {$job['id']}";
                $newCrontab[] = $job['command'];
            }
        }
        $newCrontab[] = $endMarker;

        $tempFile = tempnam(sys_get_temp_dir(), 'crontab_');
        if ($tempFile === false) {
            return false;
        }

        file_put_contents($tempFile, implode("\n", $newCrontab) . "\n");
        @exec("crontab " . escapeshellarg($tempFile) . " 2>/dev/null", $execOutput, $execReturnCode);
        @unlink($tempFile);

        return $execReturnCode === 0;
    }

    private function removeCrontabEntry(array $cronJob): bool
    {
        $output = [];
        $returnCode = 0;
        @exec('crontab -l 2>/dev/null', $output, $returnCode);

        if ($returnCode !== 0) {
            return true;
        }

        $jobId = $cronJob['id'];
        $newCrontab = [];
        $inForgeHubSection = false;
        $skipNext = false;

        foreach ($output as $line) {
            $trimmed = trim($line);

            if ($trimmed === '# ForgeHub Cron Jobs') {
                $inForgeHubSection = true;
                $newCrontab[] = $line;
                continue;
            }

            if ($inForgeHubSection) {
                if (preg_match('/^# Job ID: (.+)$/', $trimmed, $matches)) {
                    if ($matches[1] === $jobId) {
                        $skipNext = true;
                        continue;
                    }
                } elseif ($trimmed === '# End ForgeHub Cron Jobs') {
                    $inForgeHubSection = false;
                } elseif ($skipNext) {
                    $skipNext = false;
                    continue;
                }
            }

            $newCrontab[] = $line;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'crontab_');
        if ($tempFile === false) {
            return false;
        }

        file_put_contents($tempFile, implode("\n", $newCrontab) . "\n");
        @exec("crontab " . escapeshellarg($tempFile) . " 2>/dev/null", $execOutput, $execReturnCode);
        @unlink($tempFile);

        return $execReturnCode === 0;
    }

    private function updateCrontabEntry(array $cronJob): bool
    {
        return $this->rebuildCrontab();
    }
}
