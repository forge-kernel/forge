<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Services;

use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Attributes\Service;
use PDO;

#[Service]
final class MigrationRepositoryService
{
    private const string MIGRATIONS_TABLE = "forge_migrations";

    public function __construct(
        private readonly DatabaseConnectionInterface $connection
    ) {}

    /**
     * Get all migration names that have been run
     */
    public function getRanMigrations(): array
    {
        $stmt = $this->connection->query(
            "SELECT migration FROM " . self::MIGRATIONS_TABLE
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get migrations for rollback with filters
     */
    public function getMigrationsForRollback(
        int $steps,
        ?string $type = null,
        ?string $module = null,
        ?string $group = null,
        ?int $batch = null
    ): array {
        $sql = "SELECT migration FROM " . self::MIGRATIONS_TABLE . " WHERE 1=1";
        $params = [];

        if ($batch === null) {
            $lastBatch = $this->getLastBatch();
            $minBatch = $lastBatch - $steps + 1;
            $sql .= " AND batch >= ?";
            $params[] = $minBatch;
        } else {
            $sql .= " AND batch = ?";
            $params[] = $batch;
        }

        if ($type !== null && strtolower($type) !== "all") {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        if ($module !== null) {
            $sql .= " AND module = ?";
            $params[] = $module;
        }

        if ($group !== null) {
            $sql .= " AND migration_group = ?";
            $params[] = $group;
        }

        $sql .= " ORDER BY batch DESC, migration DESC";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Record a migration as completed
     */
    public function recordMigration(
        string $migrationName,
        int $batch,
        ?string $type = null,
        ?string $module = null,
        ?string $group = null
    ): bool {
        $stmt = $this->connection->prepare(
            "INSERT INTO " . self::MIGRATIONS_TABLE . 
            " (migration, batch, type, module, migration_group)
            VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $migrationName,
            $batch,
            $type,
            $module,
            $group
        ]);
    }

    /**
     * Record multiple migrations in bulk
     */
    public function recordMigrations(array $migrations): bool
    {
        if (empty($migrations)) {
            return true;
        }

        $this->connection->beginTransaction();
        try {
            $stmt = $this->connection->prepare(
                "INSERT INTO " . self::MIGRATIONS_TABLE . 
                " (migration, batch, type, module, migration_group)
                VALUES (?, ?, ?, ?, ?)"
            );

            foreach ($migrations as $migration) {
                $stmt->execute([
                    $migration['migration'],
                    $migration['batch'],
                    $migration['type'] ?? null,
                    $migration['module'] ?? null,
                    $migration['group'] ?? null
                ]);
            }

            $this->connection->commit();
            return true;
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Remove a migration record
     */
    public function removeMigration(string $migrationName): bool
    {
        $stmt = $this->connection->prepare(
            "DELETE FROM " . self::MIGRATIONS_TABLE . " WHERE migration = ?"
        );
        return $stmt->execute([$migrationName]);
    }

    /**
     * Get the last batch number
     */
    public function getLastBatch(): int
    {
        $stmt = $this->connection->query(
            "SELECT MAX(batch) FROM " . self::MIGRATIONS_TABLE
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get the next batch number
     */
    public function getNextBatch(): int
    {
        return $this->getLastBatch() + 1;
    }

    /**
     * Check if a migration has been run
     */
    public function hasMigration(string $migrationName): bool
    {
        $stmt = $this->connection->prepare(
            "SELECT COUNT(*) FROM " . self::MIGRATIONS_TABLE . " WHERE migration = ?"
        );
        $stmt->execute([$migrationName]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Get migration metadata
     */
    public function getMigrationMetadata(string $migrationName): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM " . self::MIGRATIONS_TABLE . " WHERE migration = ?"
        );
        $stmt->execute([$migrationName]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all migrations in a batch
     */
    public function getMigrationsInBatch(int $batch): array
    {
        $stmt = $this->connection->prepare(
            "SELECT migration FROM " . self::MIGRATIONS_TABLE . " WHERE batch = ? ORDER BY migration"
        );
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get all batches
     */
    public function getAllBatches(): array
    {
        $stmt = $this->connection->query(
            "SELECT DISTINCT batch FROM " . self::MIGRATIONS_TABLE . " ORDER BY batch DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Count migrations by type
     */
    public function countMigrationsByType(?string $type = null): int
    {
        $sql = "SELECT COUNT(*) FROM " . self::MIGRATIONS_TABLE;
        $params = [];

        if ($type !== null) {
            $sql .= " WHERE type = ?";
            $params[] = $type;
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get migrations filtered by multiple criteria
     */
    public function getMigrationsByFilters(
        ?string $type = null,
        ?string $module = null,
        ?string $group = null,
        ?int $batch = null,
        int $limit = 0,
        int $offset = 0
    ): array {
        $sql = "SELECT migration, batch, type, module, migration_group FROM " . self::MIGRATIONS_TABLE . " WHERE 1=1";
        $params = [];

        if ($type !== null && strtolower($type) !== "all") {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        if ($module !== null) {
            $sql .= " AND module = ?";
            $params[] = $module;
        }

        if ($group !== null) {
            $sql .= " AND migration_group = ?";
            $params[] = $group;
        }

        if ($batch !== null) {
            $sql .= " AND batch = ?";
            $params[] = $batch;
        }

        $sql .= " ORDER BY batch DESC, migration DESC";

        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}