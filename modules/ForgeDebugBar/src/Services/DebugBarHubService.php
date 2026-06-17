<?php

declare(strict_types=1);

namespace App\Modules\ForgeDebugBar\Services;

use App\Modules\ForgeDebugBar\DebugBar;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class DebugBarHubService
{
    private const string SESSION_KEY = 'forge_debugbar_latest_data';

    public function getLatestData(): ?array
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return null;
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public function storeLatestData(): void
    {
        try {
            $debugBar = DebugBar::getInstance();
            $data = $debugBar->getData();
            $_SESSION[self::SESSION_KEY] = $data;
        } catch (\Throwable) {
        }
    }

    public function formatDataForDisplay(?array $data): array
    {
        if (!$data) {
            return [
                'overview' => [],
                'queries' => [],
                'timeline' => [],
                'messages' => [],
                'exceptions' => [],
                'views' => [],
                'route' => [],
                'session' => [],
                'request' => [],
            ];
        }

        return [
            'overview' => $this->formatOverview($data),
            'queries' => $data['Database'] ?? [],
            'timeline' => $data['timeline'] ?? [],
            'messages' => $data['messages'] ?? [],
            'exceptions' => $data['exceptions'] ?? [],
            'views' => $data['views'] ?? [],
            'route' => $data['route'] ?? [],
            'session' => $data['session'] ?? [],
            'request' => $data['request'] ?? [],
        ];
    }

    private function formatOverview(array $data): array
    {
        $memory = $data['memory'] ?? [];
        $time = $data['time'] ?? null;

        // Parse memory values - they come as strings like "X MB" or as raw bytes
        $memoryCurrent = 0;
        $memoryPeak = 0;

        if (is_array($memory)) {
            // MemoryCollector returns strings like "X MB", parse them
            if (isset($memory['current']) && is_string($memory['current'])) {
                $memoryCurrent = $this->parseMemoryString($memory['current']);
            } elseif (isset($memory['current']) && is_numeric($memory['current'])) {
                $memoryCurrent = (int) $memory['current'];
            }

            if (isset($memory['peak']) && is_string($memory['peak'])) {
                $memoryPeak = $this->parseMemoryString($memory['peak']);
            } elseif (isset($memory['peak']) && is_numeric($memory['peak'])) {
                $memoryPeak = (int) $memory['peak'];
            }
        }

        // Parse time - TimeCollector returns string like "X.XXms"
        $executionTime = 0;
        if (is_string($time)) {
            $executionTime = $this->parseTimeString($time);
        } elseif (is_numeric($time)) {
            $executionTime = (float) $time;
        } elseif (is_array($time) && isset($time['execution_time'])) {
            $executionTime = (float) ($time['execution_time'] ?? 0);
        }

        return [
            'php_version' => $data['php_version'] ?? phpversion(),
            'memory_current' => $memoryCurrent,
            'memory_peak' => $memoryPeak,
            'memory_limit' => $memory['limit'] ?? 0,
            'execution_time' => $executionTime,
        ];
    }

    private function parseMemoryString(string $memory): int
    {
        // Parse strings like "12.34 MB" or "1234 KB" to bytes
        if (preg_match('/([\d.]+)\s*(KB|MB|GB|B)/i', $memory, $matches)) {
            $value = (float) $matches[1];
            $unit = strtoupper($matches[2]);

            switch ($unit) {
                case 'GB':
                    return (int) ($value * 1024 * 1024 * 1024);
                case 'MB':
                    return (int) ($value * 1024 * 1024);
                case 'KB':
                    return (int) ($value * 1024);
                case 'B':
                    return (int) $value;
            }
        }

        return 0;
    }

    private function parseTimeString(string $time): float
    {
        // Parse strings like "123.45ms" to float milliseconds
        if (preg_match('/([\d.]+)\s*ms/i', $time, $matches)) {
            return (float) $matches[1];
        }

        return 0.0;
    }
}
