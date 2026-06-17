<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class MonitoringService
{
    public function getCpuLoad(): array
    {
        $load = sys_getloadavg();

        if ($load === false) {
            return [
                '1min' => null,
                '5min' => null,
                '15min' => null,
                'available' => false,
            ];
        }

        return [
            '1min' => round($load[0], 2),
            '5min' => round($load[1], 2),
            '15min' => round($load[2], 2),
            'available' => true,
        ];
    }

    public function getMemoryInfo(): array
    {
        $phpMemory = [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => $this->convertToBytes(ini_get('memory_limit')),
        ];

        $systemMemory = $this->getSystemMemory();

        return [
            'php' => $phpMemory,
            'system' => $systemMemory,
        ];
    }

    public function getDiskInfo(): array
    {
        $rootPath = '/';
        if (PHP_OS_FAMILY === 'Windows') {
            $rootPath = 'C:\\';
        }

        $rootTotal = disk_total_space($rootPath);
        $rootFree = disk_free_space($rootPath);
        $rootUsed = $rootTotal !== false && $rootFree !== false ? $rootTotal - $rootFree : null;

        $storagePath = BASE_PATH . '/storage';
        $storageTotal = is_dir($storagePath) ? disk_total_space($storagePath) : null;
        $storageFree = is_dir($storagePath) ? disk_free_space($storagePath) : null;
        $storageUsed = $storageTotal !== false && $storageFree !== false ? $storageTotal - $storageFree : null;

        return [
            'root' => [
                'total' => $rootTotal !== false ? $rootTotal : 0,
                'used' => $rootUsed ?? 0,
                'free' => $rootFree !== false ? $rootFree : 0,
                'percentage' => $rootTotal !== false && $rootUsed !== null ? round(($rootUsed / $rootTotal) * 100, 1) : 0,
            ],
            'storage' => [
                'total' => $storageTotal !== false ? $storageTotal : 0,
                'used' => $storageUsed ?? 0,
                'free' => $storageFree !== false ? $storageFree : 0,
                'percentage' => $storageTotal !== false && $storageUsed !== null ? round(($storageUsed / $storageTotal) * 100, 1) : 0,
            ],
        ];
    }

    public function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'os' => PHP_OS,
            'os_family' => PHP_OS_FAMILY,
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'uptime' => $this->getUptime(),
            'process_count' => $this->getProcessCount(),
        ];
    }

    public function getAllMetrics(): array
    {
        return [
            'cpu' => $this->getCpuLoad(),
            'memory' => $this->getMemoryInfo(),
            'disk' => $this->getDiskInfo(),
            'system' => $this->getSystemInfo(),
        ];
    }

    private function getSystemMemory(): array
    {
        if (PHP_OS_FAMILY === 'Linux') {
            return $this->getLinuxMemory();
        }

        if (PHP_OS_FAMILY === 'Darwin') {
            return $this->getMacOSMemory();
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return $this->getWindowsMemory();
        }

        return [
            'total' => 0,
            'used' => 0,
            'free' => 0,
            'available' => false,
        ];
    }

    private function getLinuxMemory(): array
    {
        $meminfo = @file_get_contents('/proc/meminfo');
        if ($meminfo === false) {
            return [
                'total' => 0,
                'used' => 0,
                'free' => 0,
                'available' => false,
            ];
        }

        $total = 0;
        $free = 0;
        $available = 0;

        foreach (explode("\n", $meminfo) as $line) {
            if (preg_match('/^MemTotal:\s+(\d+)\s+kB/i', $line, $matches)) {
                $total = (int)$matches[1] * 1024;
            } elseif (preg_match('/^MemFree:\s+(\d+)\s+kB/i', $line, $matches)) {
                $free = (int)$matches[1] * 1024;
            } elseif (preg_match('/^MemAvailable:\s+(\d+)\s+kB/i', $line, $matches)) {
                $available = (int)$matches[1] * 1024;
            }
        }

        $used = $total > 0 ? $total - ($available > 0 ? $available : $free) : 0;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $available > 0 ? $available : $free,
            'percentage' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
            'available' => true,
        ];
    }

    private function getMacOSMemory(): array
    {
        if (!function_exists('shell_exec') || in_array('shell_exec', explode(',', ini_get('disable_functions')), true)) {
            return [
                'total' => 0,
                'used' => 0,
                'free' => 0,
                'available' => false,
            ];
        }

        $totalOutput = @shell_exec('sysctl -n hw.memsize 2>/dev/null');
        if ($totalOutput === null || trim($totalOutput) === '') {
            return [
                'total' => 0,
                'used' => 0,
                'free' => 0,
                'available' => false,
            ];
        }

        $total = (int)trim($totalOutput);
        if ($total <= 0) {
            return [
                'total' => 0,
                'used' => 0,
                'free' => 0,
                'available' => false,
            ];
        }

        $vmStat = @shell_exec('vm_stat 2>/dev/null');
        if ($vmStat === null || trim($vmStat) === '') {
            return [
                'total' => $total,
                'used' => 0,
                'free' => 0,
                'available' => false,
            ];
        }

        $freePages = 0;
        $inactivePages = 0;
        $speculativePages = 0;
        $wiredPages = 0;
        $activePages = 0;

        foreach (explode("\n", $vmStat) as $line) {
            if (preg_match('/Pages free:\s+(\d+)/', $line, $matches)) {
                $freePages = (int)$matches[1];
            } elseif (preg_match('/Pages inactive:\s+(\d+)/', $line, $matches)) {
                $inactivePages = (int)$matches[1];
            } elseif (preg_match('/Pages speculative:\s+(\d+)/', $line, $matches)) {
                $speculativePages = (int)$matches[1];
            } elseif (preg_match('/Pages wired down:\s+(\d+)/', $line, $matches)) {
                $wiredPages = (int)$matches[1];
            } elseif (preg_match('/Pages active:\s+(\d+)/', $line, $matches)) {
                $activePages = (int)$matches[1];
            }
        }

        $pageSize = 4096;
        $free = ($freePages + $inactivePages + $speculativePages) * $pageSize;
        $used = ($wiredPages + $activePages) * $pageSize;

        if ($used === 0 && $free === 0) {
            $used = $total - $free;
        }

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percentage' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
            'available' => $total > 0 && ($free > 0 || $used > 0),
        ];
    }

    private function getWindowsMemory(): array
    {
        $output = @shell_exec('wmic computersystem get TotalPhysicalMemory');
        if ($output === null) {
            return [
                'total' => 0,
                'used' => 0,
                'free' => 0,
                'available' => false,
            ];
        }

        $lines = explode("\n", trim($output));
        $total = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if (is_numeric($line)) {
                $total = (int)$line;
                break;
            }
        }

        $freeOutput = @shell_exec('wmic OS get FreePhysicalMemory');
        $free = 0;
        if ($freeOutput !== null) {
            $freeLines = explode("\n", trim($freeOutput));
            foreach ($freeLines as $line) {
                $line = trim($line);
                if (is_numeric($line)) {
                    $free = (int)$line * 1024;
                    break;
                }
            }
        }

        $used = $total > 0 ? $total - $free : 0;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percentage' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
            'available' => $total > 0,
        ];
    }

    private function getUptime(): ?string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime !== false) {
                $seconds = (float)explode(' ', trim($uptime))[0];
                return $this->formatUptime($seconds);
            }
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            $output = @shell_exec('sysctl -n kern.boottime');
            if ($output !== null) {
                if (preg_match('/sec = (\d+)/', $output, $matches)) {
                    $bootTime = (int)$matches[1];
                    $uptime = time() - $bootTime;
                    return $this->formatUptime($uptime);
                }
            }
        }

        return null;
    }

    private function formatUptime(float $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }
        if ($minutes > 0 || empty($parts)) {
            $parts[] = $minutes . 'm';
        }

        return implode(' ', $parts);
    }

    private function getProcessCount(): ?int
    {
        if (PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Darwin') {
            $output = @shell_exec('ps aux | wc -l');
            if ($output !== null) {
                return (int)trim($output) - 1;
            }
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $output = @shell_exec('tasklist | find /c /v ""');
            if ($output !== null) {
                return (int)trim($output) - 1;
            }
        }

        return null;
    }

    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '-1') {
            return 0;
        }

        $last = strtolower($value[strlen($value) - 1]);
        $value = (int)$value;

        return match ($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
