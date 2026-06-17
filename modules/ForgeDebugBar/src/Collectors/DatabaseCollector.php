<?php

namespace App\Modules\ForgeDebugBar\Collectors;

class DatabaseCollector implements CollectorInterface
{
    private array $queries = [];
    private float $slowQueryThreshold = 100;
    private float $mediumQueryThreshold = 50;

    public static function collect(...$args): array
    {
        return self::instance()->queries;
    }

    public static function instance(): self
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    public function addQuery(string $query, array $bindings, float $time, string $connectionName, string $origin): void
    {
        $this->queries[] = [
            'query' => $query,
            'bindings' => $bindings,
            'time_ms' => number_format($time, 2),
            'connection_name' => $connectionName,
            'origin' => $origin,
            'performance' => $this->classifyQueryPerformance($time)
        ];
    }

    private function classifyQueryPerformance(float $time): string
    {
        if ($time > $this->slowQueryThreshold) {
            return 'slow';
        } elseif ($time > $this->mediumQueryThreshold) {
            return 'medium';
        } else {
            return 'fast';
        }
    }

    public function reset(): void
    {
        $this->queries = [];
    }
}
