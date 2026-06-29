<?php

declare(strict_types=1);

namespace Modules\ForgeHub\Services;

use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Observability\ObservabilityConfig;

#[Service]
final class ObservabilityService implements ObservabilityServiceInterface
{
    private const TABLE_NAME = 'observability_traces';

    public function __construct(
        private readonly DatabaseConnectionInterface $connection
    ) {
    }

    public function getDashboardStats(int $hours = 24): array
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        try {
            $stmt = $this->connection->prepare("SELECT
                COUNT(*) as total_requests,
                COALESCE(AVG(duration_ms), 0) as avg_duration,
                COALESCE(SUM(query_count), 0) as total_queries,
                COALESCE(SUM(slow_query_count), 0) as slow_query_count,
                COALESCE(SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END), 0) as error_count
            FROM " . self::TABLE_NAME . "
            WHERE created_at >= :since");
            $stmt->execute(['since' => $since]);
            $summary = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            $stmt = $this->connection->prepare("SELECT
                COUNT(*) as sampled_traces,
                COUNT(DISTINCT request_path) as unique_paths
            FROM " . self::TABLE_NAME . "
            WHERE created_at >= :since AND sampled = 1");
            $stmt->execute(['since' => $since]);
            $sampled = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            return [
                'total_requests' => (int) ($summary['total_requests'] ?? 0),
                'avg_duration' => round((float) ($summary['avg_duration'] ?? 0), 2),
                'total_queries' => (int) ($summary['total_queries'] ?? 0),
                'slow_query_count' => (int) ($summary['slow_query_count'] ?? 0),
                'error_count' => (int) ($summary['error_count'] ?? 0),
                'sampled_traces' => (int) ($sampled['sampled_traces'] ?? 0),
                'unique_paths' => (int) ($sampled['unique_paths'] ?? 0),
            ];
        } catch (\Throwable) {
            return $this->emptyStats();
        }
    }

    public function getTraces(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['path'])) {
            $where[] = 'request_path LIKE :path';
            $params['path'] = '%' . $filters['path'] . '%';
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['min_duration'])) {
            $where[] = 'duration_ms >= :min_duration';
            $params['min_duration'] = (float) $filters['min_duration'];
        }

        if (!empty($filters['since'])) {
            $where[] = 'created_at >= :since';
            $params['since'] = $filters['since'];
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        try {
            $countStmt = $this->connection->prepare("SELECT COUNT(*) as total FROM " . self::TABLE_NAME . " WHERE {$whereSql}");
            $countStmt->execute($params);
            $total = (int) ($countStmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0);

            $stmt = $this->connection->prepare("SELECT * FROM " . self::TABLE_NAME . "
                WHERE {$whereSql}
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset");
            $stmt->execute(array_merge($params, [
                'limit' => $perPage,
                'offset' => $offset,
            ]));
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'data' => array_map($this->formatTraceRow(...), $rows),
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => (int) max(ceil($total / $perPage), 1),
            ];
        } catch (\Throwable) {
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => 1,
            ];
        }
    }

    public function getTraceDetail(string $id): ?array
    {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM " . self::TABLE_NAME . " WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            return $this->formatTraceRow($row, true);
        } catch (\Throwable) {
            return null;
        }
    }

    public function getSlowQueries(int $limit = 25, float $minDurationMs = 100): array
    {
        $since = date('Y-m-d H:i:s', strtotime('-7 days'));
        $slowThreshold = $minDurationMs > 0 ? $minDurationMs : ObservabilityConfig::slowQueryMs();

        try {
            $stmt = $this->connection->prepare("SELECT spans FROM " . self::TABLE_NAME . "
                WHERE sampled = 1 AND slow_query_count > 0 AND created_at >= :since
                ORDER BY created_at DESC
                LIMIT :limit");
            $stmt->execute([
                'since' => $since,
                'limit' => $limit * 10,
            ]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $queries = [];
            foreach ($rows as $row) {
                $spans = $this->decodeJson($row['spans'] ?? '[]');
                foreach ($spans as $span) {
                    if (($span['type'] ?? '') !== 'db') {
                        continue;
                    }

                    $duration = (float) ($span['duration_ms'] ?? 0);
                    if ($duration < $slowThreshold) {
                        continue;
                    }

                    $sql = (string) ($span['metadata']['sql'] ?? '');
                    if ($sql === '') {
                        continue;
                    }

                    $normalized = $this->normalizeSql($sql);
                    if (!isset($queries[$normalized])) {
                        $queries[$normalized] = [
                            'query' => $sql,
                            'normalized' => $normalized,
                            'count' => 0,
                            'total_ms' => 0.0,
                            'max_ms' => 0.0,
                            'min_ms' => PHP_FLOAT_MAX,
                        ];
                    }

                    $queries[$normalized]['count']++;
                    $queries[$normalized]['total_ms'] += $duration;
                    $queries[$normalized]['max_ms'] = max($queries[$normalized]['max_ms'], $duration);
                    $queries[$normalized]['min_ms'] = min($queries[$normalized]['min_ms'], $duration);
                }
            }

            usort($queries, fn($a, $b) => $b['total_ms'] <=> $a['total_ms']);
            $queries = array_slice($queries, 0, $limit);

            return array_map(function ($q) {
                return [
                    'query' => $q['query'],
                    'normalized' => $q['normalized'],
                    'count' => $q['count'],
                    'avg_ms' => round($q['total_ms'] / $q['count'], 2),
                    'max_ms' => round($q['max_ms'], 2),
                    'min_ms' => $q['min_ms'] === PHP_FLOAT_MAX ? 0 : round($q['min_ms'], 2),
                    'total_ms' => round($q['total_ms'], 2),
                ];
            }, $queries);
        } catch (\Throwable) {
            return [];
        }
    }

    public function purgeOldTraces(int $days = 7): int
    {
        try {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $stmt = $this->connection->prepare("DELETE FROM " . self::TABLE_NAME . " WHERE created_at < :cutoff");
            $stmt->execute(['cutoff' => $cutoff]);
            return (int) $stmt->rowCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function formatTraceRow(array $row, bool $withSpans = false): array
    {
        $data = [
            'id' => $row['id'],
            'name' => $row['name'],
            'method' => $row['request_method'] ?? '-',
            'path' => $row['request_path'] ?? '-',
            'duration_ms' => round((float) ($row['duration_ms'] ?? 0), 2),
            'status_code' => $row['status_code'] ?? null,
            'status' => $row['status'] ?? 'ok',
            'span_count' => (int) ($row['span_count'] ?? 0),
            'query_count' => (int) ($row['query_count'] ?? 0),
            'slow_query_count' => (int) ($row['slow_query_count'] ?? 0),
            'error_count' => (int) ($row['error_count'] ?? 0),
            'sampled' => (bool) ($row['sampled'] ?? 0),
            'created_at' => $row['created_at'],
        ];

        if ($withSpans) {
            $data['spans'] = $this->decodeJson($row['spans'] ?? '[]');
            $data['tags'] = $this->decodeJson($row['tags'] ?? '{}');
            $data['peak_memory_bytes'] = (int) ($row['peak_memory_bytes'] ?? 0);
        }

        return $data;
    }

    private function decodeJson(string $json): array
    {
        if ($json === '' || $json === null) {
            return [];
        }
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function normalizeSql(string $sql): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($sql));
        $normalized = preg_replace('/\b\d+\b/', '?', $normalized);
        $normalized = preg_replace("/'[^']*'/", "'?", $normalized);
        $normalized = preg_replace('/"[^"]*"/', '"?', $normalized);
        return trim($normalized);
    }

    private function emptyStats(): array
    {
        return [
            'total_requests' => 0,
            'avg_duration' => 0.0,
            'total_queries' => 0,
            'slow_query_count' => 0,
            'error_count' => 0,
            'sampled_traces' => 0,
            'unique_paths' => 0,
        ];
    }
}
