<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\Tests\Concerns;

use Modules\ForgeDatabaseSQL\DB\Connection;
use Modules\ForgeDatabaseSQL\DB\DatabaseConfig;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;

trait SqliteDatabase
{
    protected function sqliteConnection(): Connection
    {
        return new Connection(new DatabaseConfig('sqlite', ':memory:'));
    }

    protected function tableExists(
        DatabaseConnectionInterface $conn,
        string $table,
    ): bool {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = ?",
        );
        $stmt->execute([$table]);
        $exists = (int) $stmt->fetchColumn() > 0;
        $stmt->closeCursor();
        return $exists;
    }

    /**
     * Executes a DROP TABLE inside a transaction. On SQLite this fails with
     * "database table is locked" if any previously executed SELECT left an
     * open cursor holding a read lock.
     */
    protected function assertCanDropTableInsideTransaction(
        DatabaseConnectionInterface $conn,
        string $table,
    ): void {
        $conn->exec("CREATE TABLE {$table} (id INTEGER)");
        $conn->beginTransaction();
        try {
            $conn->exec("DROP TABLE {$table}");
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
        $this->assertFalse($this->tableExists($conn, $table));
    }
}
