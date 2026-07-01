<?php

declare(strict_types=1);

namespace Modules\ForgeTesting\Traits;

trait MetricsProvider
{
    private ?array $_metricsRow = null;

    private array $_profilePoints = [];

    public function recordMetrics(array $data): void
    {
        $this->_metricsRow = $data;
    }

    public function getMetricsRow(): ?array
    {
        return $this->_metricsRow;
    }

    public function startProfile(string $label): void
    {
        $this->_profilePoints[$label] = [
            'wall' => hrtime(true),
            'cpu' => getrusage(),
            'mem' => memory_get_usage(true),
        ];
    }

    public function endProfile(string $label): array
    {
        $endWall = hrtime(true);
        $endCpu = getrusage();
        $endMem = memory_get_usage(true);

        $start = $this->_profilePoints[$label] ?? throw new \RuntimeException("No profile point started for: {$label}");

        $wallMs = ($endWall - $start['wall']) / 1_000_000;

        $cpuUs = ($endCpu['ru_utime.tv_sec'] - $start['cpu']['ru_utime.tv_sec']) * 1_000_000
               + ($endCpu['ru_utime.tv_usec'] - $start['cpu']['ru_utime.tv_usec'])
               + ($endCpu['ru_stime.tv_sec'] - $start['cpu']['ru_stime.tv_sec']) * 1_000_000
               + ($endCpu['ru_stime.tv_usec'] - $start['cpu']['ru_stime.tv_usec']);
        $cpuMs = $cpuUs / 1000;

        $memDelta = ($endMem - $start['mem']) / 1_048_576;

        return [
            'wall_ms' => $wallMs,
            'cpu_ms' => $cpuMs,
            'cpu_pct' => $wallMs > 0 ? round(($cpuMs / $wallMs) * 100, 1) : 0.0,
            'memory_mb' => $memDelta,
        ];
    }

    public function profile(callable $fn): array
    {
        $startWall = hrtime(true);
        $startCpu = getrusage();
        $startMem = memory_get_usage(true);

        $result = $fn();

        $endWall = hrtime(true);
        $endCpu = getrusage();
        $endMem = memory_get_usage(true);

        $wallMs = ($endWall - $startWall) / 1_000_000;

        $cpuUs = ($endCpu['ru_utime.tv_sec'] - $startCpu['ru_utime.tv_sec']) * 1_000_000
               + ($endCpu['ru_utime.tv_usec'] - $startCpu['ru_utime.tv_usec'])
               + ($endCpu['ru_stime.tv_sec'] - $startCpu['ru_stime.tv_sec']) * 1_000_000
               + ($endCpu['ru_stime.tv_usec'] - $startCpu['ru_stime.tv_usec']);
        $cpuMs = $cpuUs / 1000;

        $memDelta = ($endMem - $startMem) / 1_048_576;

        return [
            'result' => $result,
            'wall_ms' => $wallMs,
            'cpu_ms' => $cpuMs,
            'cpu_pct' => $wallMs > 0 ? round(($cpuMs / $wallMs) * 100, 1) : 0.0,
            'memory_mb' => $memDelta,
        ];
    }
}
