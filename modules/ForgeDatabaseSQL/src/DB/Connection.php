<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\DB;

use Modules\ForgeRouter\Collectors\DatabaseCollector;
use Forge\Core\Contracts\Database\DatabaseConfigInterface;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Container;
use Forge\Core\Helpers\Debuger;
use Forge\Core\Observability\ObservabilityManager;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class Connection implements DatabaseConnectionInterface
{
  private PDO $pdo;

  public function __construct(DatabaseConfigInterface $config)
  {
    $dsn = $config->getDsn();
    $pdoOptionsToUse = array_merge(
      [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ],
      $config->getOptions(),
    );

    try {
      $this->pdo = new PDO(
        $dsn,
        $config->getUsername(),
        $config->getPassword(),
        $pdoOptionsToUse,
      );

      if ($config->getDriver() === "sqlite") {
        $this->pdo->exec("PRAGMA journal_mode = WAL;");
        $this->pdo->exec("PRAGMA synchronous = NORMAL;");
        $this->pdo->exec("PRAGMA foreign_keys = ON;");
        $this->pdo->exec("PRAGMA busy_timeout = 2000;");
      }
    } catch (PDOException $exception) {
      throw new RuntimeException(
        "Database connection failed: " . $exception->getMessage(),
      );
    }
  }

  public function exec(string $statement): int|false
  {
    $startTime = microtime(true);
    $result = $this->pdo->exec($statement);
    $this->collectQuery($statement, [], (microtime(true) - $startTime) * 1000, 'exec');
    return $result;
  }

  public function getPdo(): PDO
  {
    return $this->pdo;
  }

  public function beginTransaction(): bool
  {
    return $this->pdo->beginTransaction();
  }

  public function commit(): bool
  {
    return $this->pdo->commit();
  }

  public function rollBack(): bool
  {
    return $this->pdo->rollBack();
  }

  public function prepare(string $statement): PDOStatement
  {
    return $this->pdo->prepare($statement);
  }

  public function query(string $statement): PDOStatement
  {
    $startTime = microtime(true);
    $result = $this->pdo->query($statement);
    $this->collectQuery($statement, [], (microtime(true) - $startTime) * 1000, 'query');
    return $result;
  }

  /**
   * Collect database query for debugging.
   */
    public function collectQuery(string $query, array $bindings, float $timeMs, string $method): void
    {
        try {
            $container = Container::getInstance();
            if ($container->has(DatabaseCollector::class)) {
                /** @var DatabaseCollector $collector */
                $collector = $container->get(DatabaseCollector::class);
                $origin = Debuger::backtraceOrigin();
                $connectionName = $this->getDriver();
                $collector->addQuery($query, $bindings, $timeMs, $connectionName, $origin);
            }

            ObservabilityManager::getInstance()?->recordQuery($query, $bindings, $timeMs, Debuger::backtraceOrigin());
        } catch (\Throwable $e) {

        }
    }

  public function getDriver(): string
  {
    return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
  }
}
