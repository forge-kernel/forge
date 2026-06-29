<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Collectors;

use Modules\ForgeRouter\Contracts\RequestCollectorInterface;
use Forge\Core\DI\Attributes\Service;
use Modules\ForgeRouter\Http\Request;
use Forge\Core\Module\Attributes\Provides;

/**
 * Database query collector that tracks all database queries executed during a request.
 * This collector is independent of any specific module and can be used by any module.
 */
#[Service]
#[Provides(RequestCollectorInterface::class, version: '1.0.0')]
final class DatabaseCollector implements RequestCollectorInterface
{
  private array $queries = [];
  private float $slowQueryThreshold = 100.0;
  private float $mediumQueryThreshold = 50.0;

  /**
   * Collect database queries for the request.
   * This is called by the Kernel during request handling.
   *
   * @param Request $request The current request
   * @return array The collected database queries
   */
  public function collect(Request $request): array
  {
    return $this->queries;
  }

  /**
   * Add a database query to the collection.
   *
   * @param string $query The SQL query
   * @param array $bindings The query bindings
   * @param float $time Time taken in milliseconds
   * @param string $connectionName The connection name
   * @param string $origin The origin/caller of the query
   * @return void
   */
  public function addQuery(
    string $query,
    array $bindings = [],
    float $time = 0.0,
    string $connectionName = 'default',
    string $origin = ''
  ): void {
    $this->queries[] = [
      'query' => $query,
      'bindings' => $bindings,
      'time_ms' => number_format($time, 2),
      'connection_name' => $connectionName,
      'origin' => $origin,
      'performance' => $this->classifyQueryPerformance($time),
    ];
  }

  /**
   * Classify query performance based on execution time.
   *
   * @param float $time Time in milliseconds
   * @return string 'slow', 'medium', or 'fast'
   */
  private function classifyQueryPerformance(float $time): string
  {
    if ($time > $this->slowQueryThreshold) {
      return 'slow';
    } elseif ($time > $this->mediumQueryThreshold) {
      return 'medium';
    }
    return 'fast';
  }

  /**
   * Get all collected queries.
   *
   * @return array
   */
  public function getQueries(): array
  {
    return $this->queries;
  }

  /**
   * Reset the collector (clear all queries).
   *
   * @return void
   */
  public function reset(): void
  {
    $this->queries = [];
  }

  /**
   * Set query performance thresholds.
   *
   * @param float $slowThreshold Slow query threshold in milliseconds
   * @param float $mediumThreshold Medium query threshold in milliseconds
   * @return void
   */
  public function setThresholds(float $slowThreshold, float $mediumThreshold): void
  {
    $this->slowQueryThreshold = $slowThreshold;
    $this->mediumQueryThreshold = $mediumThreshold;
  }
}
