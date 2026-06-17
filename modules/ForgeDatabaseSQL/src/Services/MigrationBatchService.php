<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Services;

use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Attributes\Service;
use PDO;

#[Service]
final class MigrationBatchService
{
    private const string MIGRATIONS_TABLE = "forge_migrations";

    public function __construct(
        private readonly DatabaseConnectionInterface $connection
    ) {}

    /**
     * Get the next available batch number
     */
    public function getNextBatch(): int
    {
        $stmt = $this->connection->query(
            "SELECT MAX(batch) FROM " . self::MIGRATIONS_TABLE
        );
        return (int) $stmt->fetchColumn() + 1;
    }

    /**
     * Get the current (last) batch number
     */
    public function getLastBatch(): int
    {
        $stmt = $this->connection->query(
            "SELECT MAX(batch) FROM " . self::MIGRATIONS_TABLE
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get all batches in descending order
     */
    public function getAllBatches(): array
    {
        $stmt = $this->connection->query(
            "SELECT DISTINCT batch FROM " . self::MIGRATIONS_TABLE . " ORDER BY batch DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get migrations in a specific batch
     */
    public function getMigrationsInBatch(int $batch): array
    {
        $stmt = $this->connection->prepare(
            "SELECT migration, type, module, migration_group 
             FROM " . self::MIGRATIONS_TABLE . " 
             WHERE batch = ? 
             ORDER BY migration"
        );
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get batch numbers for rollback based on steps
     */
    public function getBatchesForRollback(int $steps): array
    {
        if ($steps <= 0) {
            return [];
        }

        $lastBatch = $this->getLastBatch();
        $minBatch = max(1, $lastBatch - $steps + 1);

        $stmt = $this->connection->prepare(
            "SELECT DISTINCT batch FROM " . self::MIGRATIONS_TABLE . 
            " WHERE batch >= ? AND batch <= ? 
             ORDER BY batch DESC"
        );
        $stmt->execute([$minBatch, $lastBatch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get migrations to rollback for the given steps
     */
    public function getMigrationsToRollback(int $steps, array $filters = []): array
    {
        $sql = "SELECT migration, type, module, migration_group, batch 
                FROM " . self::MIGRATIONS_TABLE . " 
                WHERE 1=1";
        $params = [];

        if ($steps > 0) {
            $lastBatch = $this->getLastBatch();
            $minBatch = max(1, $lastBatch - $steps + 1);
            $sql .= " AND batch >= ?";
            $params[] = $minBatch;
        }

        if (!empty($filters['type']) && strtolower($filters['type']) !== 'all') {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['module'])) {
            $sql .= " AND module = ?";
            $params[] = $filters['module'];
        }

        if (!empty($filters['group'])) {
            $sql .= " AND migration_group = ?";
            $params[] = $filters['group'];
        }

        $sql .= " ORDER BY batch DESC, migration DESC";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a batch exists
     */
    public function batchExists(int $batch): bool
    {
        $stmt = $this->connection->prepare(
            "SELECT COUNT(*) FROM " . self::MIGRATIONS_TABLE . " WHERE batch = ?"
        );
        $stmt->execute([$batch]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Count migrations in a batch
     */
    public function countMigrationsInBatch(int $batch): int
    {
        $stmt = $this->connection->prepare(
            "SELECT COUNT(*) FROM " . self::MIGRATIONS_TABLE . " WHERE batch = ?"
        );
        $stmt->execute([$batch]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get batch statistics
     */
    public function getBatchStatistics(): array
    {
        $stmt = $this->connection->query(
            "SELECT batch, 
                    COUNT(*) as migration_count,
                    COUNT(DISTINCT type) as type_count,
                    COUNT(DISTINCT module) as module_count
             FROM " . self::MIGRATIONS_TABLE . " 
             GROUP BY batch 
             ORDER BY batch DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the first batch number
     */
    public function getFirstBatch(): int
    {
        $stmt = $this->connection->query(
            "SELECT MIN(batch) FROM " . self::MIGRATIONS_TABLE
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get total number of batches
     */
    public function getTotalBatches(): int
    {
        $stmt = $this->connection->query(
            "SELECT COUNT(DISTINCT batch) FROM " . self::MIGRATIONS_TABLE
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get batches in a range
     */
    public function getBatchesInRange(int $start, int $end): array
    {
        $stmt = $this->connection->prepare(
            "SELECT DISTINCT batch FROM " . self::MIGRATIONS_TABLE . 
            " WHERE batch >= ? AND batch <= ? 
             ORDER BY batch"
        );
        $stmt->execute([$start, $end]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Validate that we can rollback the specified steps
     */
    public function canRollbackSteps(int $steps): bool
    {
        if ($steps <= 0) {
            return false;
        }

        $totalBatches = $this->getTotalBatches();
        return $steps <= $totalBatches;
    }

    /**
     * Get the batch numbers that would be affected by a rollback
     */
    public function getAffectedBatches(int $steps): array
    {
        if (!$this->canRollbackSteps($steps)) {
            return [];
        }

        return $this->getBatchesForRollback($steps);
    }
}