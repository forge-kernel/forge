<?php

namespace App\Modules\ForgeTesting\Traits;

trait PerformanceTesting
{
    protected function assertMaxExecutionTime(
        float $maxSeconds,
        callable $test,
    ): void {
        $start = microtime(true);
        $test();
        $duration = microtime(true) - $start;

        $this->assertTrue(
            $duration <= $maxSeconds,
            "Execution time exceeded: {$duration}s (max: {$maxSeconds}s)",
        );
    }

    protected function benchmark(callable $test, int $iterations = 1000): array
    {
        $times = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $test();
            $times[] = microtime(true) - $start;
        }

        return [
            "avg" => array_sum($times) / count($times),
            "min" => min($times),
            "max" => max($times),
            "total" => array_sum($times),
        ];
    }
}
