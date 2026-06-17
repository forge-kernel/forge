<?php

namespace App\Modules\ForgeDebugBar\Collectors;

class MemoryCollector implements CollectorInterface
{
    private int $startMemory;

    public function __construct()
    {
        $this->startMemory = memory_get_usage();
    }

    public static function collect(...$args): array
    {
        return self::instance()->getMemoryUsage();
    }

    public static function instance(): self
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    public function getMemoryUsage(): array
    {
        $currentMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();

        $usedMemoryBytes = $currentMemory - $this->startMemory;
        $usedMemoryMB = round($usedMemoryBytes / (1024 * 1024), 2);
        $peakMemoryMB = round($peakMemory / (1024 * 1024), 2);
        $currentMemoryMB = round($currentMemory / (1024 * 1024), 2);

        return [
            'current' => $currentMemoryMB . ' MB',
            'used' => $usedMemoryMB . ' MB',
            'peak' => $peakMemoryMB . ' MB',
            'percentage' => $this->getMemoryPercentage($currentMemory)
        ];
    }

    private function getMemoryPercentage(int $currentMemory): string
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit === '-1') {
            return 'Unlimited';
        }

        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        if ($memoryLimitBytes > 0) {
            $percentage = round(($currentMemory / $memoryLimitBytes) * 100, 1);
            return $percentage . '%';
        }

        return 'Unknown';
    }

    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}
