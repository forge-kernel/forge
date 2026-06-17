<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant;


use App\Modules\ForgeDatabaseSQL\DB\Connection;
use PDO;

final class LazyTenantConnection extends Connection
{
    private ?Connection $real = null;
    private \Closure $factory;

    public function __construct(\Closure $factory)
    {
        $this->factory = $factory;
    }

    public function getPdo(): \PDO
    {
        return $this->real()->getPdo();
    }

    private function real(): Connection
    {
        return $this->real ??= ($this->factory)();
    }

    public function beginTransaction(): bool
    {
        return $this->real()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->real()->commit();
    }

    public function rollBack(): bool
    {
        return $this->real()->rollBack();
    }

    public function prepare(string $statement): \PDOStatement
    {
        return $this->real()->prepare($statement);
    }

    public function query(string $statement): \PDOStatement
    {
        return $this->real()->query($statement);
    }

    public function exec(string $statement): int|false
    {
        return $this->real()->exec($statement);
    }

    public function getDriver(): string
    {
        return $this->real->getDriver();
    }
}