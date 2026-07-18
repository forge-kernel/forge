<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Services;

use Modules\ForgeHub\Models\LogEntry;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Module\Attributes\Requires;
use Forge\Core\Config\Config;
use Generator;
use SplFileInfo;
use DirectoryIterator;

#[Requires(Config::class)]

final class LogService
{
    private const MAX_FILE_SIZE = 10485760; // 10MB

    private const LOG_PATH = BASE_PATH . '/storage/logs/';
    private string $logPath;

    public function __construct(
        private Config $config
    ) {
        $this->logPath = self::LOG_PATH;
    }

    /** @return SplFileInfo[] */
    public function getLogFiles(): array
    {
        if (!is_dir(self::LOG_PATH)) {
            return [];
        }

        $iterator = new DirectoryIterator(self::LOG_PATH);
        $files = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getSize() < self::MAX_FILE_SIZE && !$file->isDot() && strpos($file->getFilename(), '.') !== 0) {
                $files[] = $file->getFileInfo();
            }
        }

        // Sort by modification time, newest first
        usort($files, fn($a, $b) => $b->getMTime() <=> $a->getMTime());

        return $files;
    }

    /** @return Generator<LogEntry> */
    public function getLogEntries(
        ?string $filename = null,
        ?string $search = null,
        ?string $date = null,
        ?string $level = null,
        ?string $module = null,
        ?string $fingerprint = null,
    ): Generator {
        $file = $this->validateFile($filename);

        foreach ($this->readFileLines($file) as $line) {
            try {
                $entry = LogEntry::fromString($line);

                if ($this->matchesFilters($entry, $search, $date, $level, $module, $fingerprint)) {
                    yield $entry;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
    }

    /**
     * Get statistics for a log file.
     * @return array{total: int, byLevel: array<string, int>, byModule: array<string, int>}
     */
    public function getStats(string $filename): array
    {
        $stats = [
            'total' => 0,
            'byLevel' => [],
            'byModule' => [],
        ];

        $file = $this->validateFile($filename);

        foreach ($this->readFileLines($file) as $line) {
            try {
                $entry = LogEntry::fromString($line);
                $stats['total']++;

                $level = strtoupper($entry->level);
                $stats['byLevel'][$level] = ($stats['byLevel'][$level] ?? 0) + 1;

                if ($entry->module) {
                    $stats['byModule'][$entry->module] = ($stats['byModule'][$entry->module] ?? 0) + 1;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        arsort($stats['byLevel']);
        arsort($stats['byModule']);

        return $stats;
    }

    /**
     * Get unique modules from a log file.
     * @return string[]
     */
    public function getModules(string $filename): array
    {
        $modules = [];

        $file = $this->validateFile($filename);

        foreach ($this->readFileLines($file) as $line) {
            try {
                $entry = LogEntry::fromString($line);
                if ($entry->module && !in_array($entry->module, $modules, true)) {
                    $modules[] = $entry->module;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        sort($modules);
        return $modules;
    }

    private function validateFile(?string $filename): SplFileInfo
    {

        if (!$filename) {
            throw new \InvalidArgumentException('Invalid log file');
        }

        $path = rtrim($this->logPath, '/') . '/' . $filename;

        if (!FileExistenceCache::exists($path)) {
            throw new \InvalidArgumentException('Invalid log file');
        }

        return new SplFileInfo($path);
    }

    private function readFileLines(SplFileInfo $file): Generator
    {
        $realPath = $file->getRealPath() ?: $file->getPathname();
        $handle = @fopen($realPath, 'r');
        if (!$handle) {
            return;
        }

        $lines = [];
        while (($line = fgets($handle)) !== false) {
            $lines[] = trim($line);
        }
        fclose($handle);

        $lines = array_slice(array_reverse($lines), 0, 1000);

        foreach ($lines as $line) {
            if (!empty($line)) {
                yield $line;
            }
        }
    }

    private function matchesFilters(
        LogEntry $entry,
        ?string $search,
        ?string $date,
        ?string $level,
        ?string $module,
        ?string $fingerprint = null,
    ): bool {
        if ($date && $entry->date->format('Y-m-d') !== $date) {
            return false;
        }

        if ($level && strtoupper($entry->level) !== strtoupper($level)) {
            return false;
        }

        if ($module && $entry->module !== $module) {
            return false;
        }

        if ($fingerprint && $entry->fingerprint && stripos($entry->fingerprint, $fingerprint) === false) {
            return false;
        }
        if ($fingerprint && !$entry->fingerprint) {
            return false;
        }

        if ($search) {
            $searchLower = strtolower($search);
            if (stripos($entry->message, $searchLower) !== false) {
                return true;
            }
            // Also search in context
            if (!empty($entry->context)) {
                $contextStr = json_encode($entry->context);
                if (stripos($contextStr, $searchLower) !== false) {
                    return true;
                }
            }
            // Search in file path
            if ($entry->file && stripos($entry->file, $searchLower) !== false) {
                return true;
            }
            return false;
        }

        return true;
    }
}
