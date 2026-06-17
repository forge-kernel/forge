<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM;

use App\Modules\ForgeRouter\Collectors\DatabaseCollector;
use Forge\Core\Contracts\Database\{DatabaseConnectionInterface, QueryBuilderInterface};
use Forge\Core\DI\Container;
use Forge\Core\Helpers\Debuger;
use PDOStatement;

final class QueryBuilder implements QueryBuilderInterface
{
  private string $lastSql = '';

  public function __construct(
    private DatabaseConnectionInterface $conn,
    private string                      $table = '',
    private array                       $select = [],
    private array                       $where = [],
    private array                       $params = [],
    private ?string                     $order = null,
    private ?int                        $limit = null,
    private ?int                        $offset = null,
    private bool                        $forUpdate = false,
    private array                       $joins = []
  )
  {
  }

  public function table(?string $name): self
  {
    return new self(
      $this->conn,
      table: $name,
      select: $this->select,
      where: $this->where,
      params: $this->params,
      order: $this->order,
      limit: $this->limit,
      offset: $this->offset,
      forUpdate: $this->forUpdate,
      joins: $this->joins
    );
  }

  public function select(string ...$cols): self
  {
    return new self(
      $this->conn,
      table: $this->table,
      select: $cols,
      where: $this->where,
      params: $this->params,
      order: $this->order,
      limit: $this->limit,
      offset: $this->offset,
      forUpdate: $this->forUpdate,
      joins: $this->joins
    );
  }

  public function whereIn(string $column, array $values): self
  {
    if ($values === []) {
      return $this->where('1', '=', '0');
    }
    $keys = [];
    $newParams = $this->params;

    foreach ($values as $v) {
      $key = ':p' . count($newParams);
      $keys[] = $key;
      $newParams[$key] = $v;
    }

    $where = [...$this->where, "$column IN (" . implode(',', $keys) . ")"];
    return new self(
      $this->conn,
      table: $this->table,
      select: $this->select,
      where: $where,
      params: $newParams,
      order: $this->order,
      limit: $this->limit,
      offset: $this->offset,
      forUpdate: $this->forUpdate,
      joins: $this->joins
    );
  }

  public function where(string $column, string $operator, mixed $value = null): self
  {
    if ($value === null) {
      $where = [...$this->where, "$column $operator"];
      return new self(
        $this->conn,
        table: $this->table,
        select: $this->select,
        where: $where,
        params: $this->params,
        order: $this->order,
        limit: $this->limit,
        offset: $this->offset,
        forUpdate: $this->forUpdate,
        joins: $this->joins
      );
    }
    $key = ':p' . count($this->params);
    $where = [...$this->where, "$column $operator $key"];
    $params = [...$this->params, $key => $value];
    return new self(
      $this->conn,
      table: $this->table,
      select: $this->select,
      where: $where,
      params: $params,
      order: $this->order,
      limit: $this->limit,
      offset: $this->offset,
      forUpdate: $this->forUpdate,
      joins: $this->joins
    );
  }

  public function whereNotIn(string $column, array $values): self
  {
    if ($values === []) {
      return $this;
    }
    $keys = [];
    $newParams = $this->params;

    foreach ($values as $v) {
      $keys[] = $key = ':p' . count($this->params);
      $key = ':p' . count($newParams);
    }
    $where = [...$this->where, "$column NOT IN (" . implode(',', $keys) . ")"];
    return new self(
      $this->conn,
      table: $this->table,
      select: $this->select,
      where: $where,
      params: $newParams,
      order: $this->order,
      limit: $this->limit,
      offset: $this->offset,
      forUpdate: $this->forUpdate,
      joins: $this->joins
    );
  }

  public function whereNull(string $column): self
  {
    return $this->where($column, 'IS', 'NULL');
  }

  public function whereNotNull(string $column): self
  {
    return $this->where($column, 'IS NOT', 'NULL');
  }

  public function orderBy(string $column, string $direction = 'ASC'): self
  {
    return new self(
      $this->conn,
      table: $this->table,
      select: $this->select,
      where: $this->where,
      params: $this->params,
      order: "$column $direction",
      limit: $this->limit,
      offset: $this->offset,
      forUpdate: $this->forUpdate,
      joins: $this->joins
    );
  }

  public function offset(int $count): self
  {
    return new self(
      $this->conn,
      table: $this->table,
      select: $this->select,
      where: $this->where,
      params: $this->params,
      order: $this->order,
      limit: $this->limit,
      offset: $count,
      forUpdate: $this->forUpdate,
      joins: $this->joins
    );
  }

  public function lockForUpdate(): self
  {
    return new self(
      $this->conn,
      table: $this->table,
      select: $this->select,
      where: $this->where,
      params: $this->params,
      order: $this->order,
      limit: $this->limit,
      offset: $this->offset,
      forUpdate: true,
      joins: $this->joins
    );
  }

  public function getRaw(): array
  {
    return $this->get();
  }

  public function selectRaw(string $expression, array $params = []): self
  {
    $this->select[] = $expression;
    if (!empty($params)) {
      $this->params = array_merge($this->params, $params);
    }
    return $this;
  }


  public function get(): array
  {
    return $this->run()->fetchAll();
  }

  private function run(): PDOStatement
  {
    $sql = $this->buildSelect();
    $startTime = microtime(true);
    $stmt = $this->conn->prepare($sql);
    $stmt->execute($this->params);
    $this->collectQuery($sql, $this->params, (microtime(true) - $startTime) * 1000, 'select');
    return $stmt;
  }

  private function buildSelect(): string
  {
    $sql = 'SELECT ' . ($this->select === [] ? '*' : implode(', ', $this->select))
      . " FROM {$this->table}";

    foreach ($this->joins as $join) {
      $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
    }

    if ($this->where !== []) {
      $sql .= ' WHERE ' . implode(' AND ', $this->where);
    }
    if ($this->order !== null) {
      $sql .= " ORDER BY {$this->order}";
    }

    if ($this->limit !== null) {
      $sql .= " LIMIT {$this->limit}";
    }
    if ($this->offset !== null && $this->offset > 0) {
      $sql .= " OFFSET {$this->offset}";
    }

    if ($this->forUpdate && in_array($this->conn->getDriver(), ['mysql', 'pgsql'], true)) {
      $sql .= ' FOR UPDATE';
    }

    return $sql;
  }

  public function execute(string $sql): void
  {
    $startTime = microtime(true);
    $this->conn->exec($sql);
    $this->collectQuery($sql, [], (microtime(true) - $startTime) * 1000, 'exec');
  }

  public function insert(array $data): int
  {
    $cols = implode(', ', array_keys($data));
    $vals = ':' . implode(', :', array_keys($data));
    $sql = "INSERT INTO {$this->table} ($cols) VALUES ($vals)";
    $startTime = microtime(true);
    $this->conn->prepare($sql)->execute($data);
    $this->collectQuery($sql, $data, (microtime(true) - $startTime) * 1000, 'insert');
    return (int)$this->conn->getPdo()->lastInsertId();
  }

  public function insertGetId(array $data): int
  {
    return $this->insert($data);
  }

  public function update(array $data): int
  {
    $set = [];
    foreach ($data as $col => $val) {
      $set[] = "$col = :u_$col";
      $this->params[':u_' . $col] = $val;
    }
    $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . $this->buildWhere();
    $startTime = microtime(true);
    $stmt = $this->conn->prepare($sql);
    $stmt->execute($this->params);
    $this->collectQuery($sql, $this->params, (microtime(true) - $startTime) * 1000, 'update');
    return $stmt->rowCount();
  }

  private function buildWhere(): string
  {
    return $this->where === [] ? '' : ' WHERE ' . implode(' AND ', $this->where);
  }

  public function delete(): int
  {
    $sql = "DELETE FROM {$this->table}" . $this->buildWhere();
    $startTime = microtime(true);
    $stmt = $this->conn->prepare($sql);
    $stmt->execute($this->params);
    $this->collectQuery($sql, $this->params, (microtime(true) - $startTime) * 1000, 'delete');
    return $stmt->rowCount();
  }

  /**
   * Collect database query for debugging.
   */
  private function collectQuery(string $query, array $bindings, float $timeMs, string $method): void
  {
    try {
      $container = Container::getInstance();
      if ($container->has(DatabaseCollector::class)) {
        /** @var DatabaseCollector $collector */
        $collector = $container->get(DatabaseCollector::class);
        $origin = Debuger::backtraceOrigin();
        $connectionName = $this->conn->getDriver();
        $collector->addQuery($query, $bindings, $timeMs, $connectionName, $origin);
      }
    } catch (\Throwable $e) {

    }
  }

  public function count(string $column = '*'): int
  {
    return (int)$this->aggregate("COUNT($column)");
  }

  private function aggregate(string $fn): mixed
  {
    $sql = "SELECT {$fn} FROM {$this->table}";

    foreach ($this->joins as $join) {
      $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
    }

    $sql .= $this->buildWhere();

    $stmt = $this->conn->prepare($sql);
    $stmt->execute($this->params);
    return $stmt->fetchColumn();
  }

  public function sum(string $column): float
  {
    return (float)$this->aggregate("SUM($column)");
  }


  public function avg(string $column): float
  {
    return (float)$this->aggregate("AVG($column)");
  }

  public function min(string $column): float
  {
    return (float)$this->aggregate("MIN($column)");
  }

  public function max(string $column): float
  {
    return (float)$this->aggregate("MAX($column)");
  }

  public function reset(): self
  {
    return new self($this->conn);
  }

  public function raw(string $sql, array $params = []): array
  {
    $stmt = $this->conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  public function whereRaw(string $sql, array $params = []): self
  {
    $newParams = $this->params;
    foreach ($params as $key => $value) {
      $paramKey = is_int($key) ? ':p' . count($newParams) : $key;
      $newParams[$paramKey] = $value;
    }
    $where = [...$this->where, $sql];
    return new self(
      $this->conn,
      table: $this->table,
      select: $this->select,
      where: $where,
      params: $newParams,
      order: $this->order,
      limit: $this->limit,
      offset: $this->offset,
      forUpdate: $this->forUpdate,
      joins: $this->joins
    );
  }

  public function leftJoin(string $t, string $a, string $op, string $b): self
  {
    $joins = [
      ...$this->joins,
      [
        'type' => 'LEFT',
        'table' => $t,
        'first' => $a,
        'operator' => $op,
        'second' => $b,
      ]
    ];
    return new self(
      $this->conn,
      table: $this->table,
      select: $this->select,
      where: $this->where,
      params: $this->params,
      order: $this->order,
      limit: $this->limit,
      offset: $this->offset,
      forUpdate: $this->forUpdate,
      joins: $joins
    );
  }

  public function join(string $t, string $a, string $op, string $b, string $type = 'INNER'): self
  {
    $joins = [
      ...$this->joins,
      [
        'type' => strtoupper($type),
        'table' => $t,
        'first' => $a,
        'operator' => $op,
        'second' => $b,
      ]
    ];
    return new self(
      $this->conn,
      table: $this->table,
      select: $this->select,
      where: $this->where,
      params: $this->params,
      order: $this->order,
      limit: $this->limit,
      offset: $this->offset,
      forUpdate: $this->forUpdate,
      joins: $joins
    );
  }

  public function rightJoin(string $t, string $a, string $op, string $b): self
  {
    $joins = [
      ...$this->joins,
      [
        'type' => 'RIGHT',
        'table' => $t,
        'first' => $a,
        'operator' => $op,
        'second' => $b,
      ]
    ];
    return new self(
      $this->conn,
      table: $this->table,
      select: $this->select,
      where: $this->where,
      params: $this->params,
      order: $this->order,
      limit: $this->limit,
      offset: $this->offset,
      forUpdate: $this->forUpdate,
      joins: $joins
    );
  }

  public function groupBy(string ...$cols): self
  {
    return $this;
  }

  public function having(string $col, string $op, mixed $val): self
  {
    return $this;
  }

  public function exists(): bool
  {
    return $this->first() !== null;
  }

  public function first(): ?array
  {
    $stmt = $this->limit(1)->run();
    return $stmt->fetch() ?: null;
  }

  public function limit(int $n): self
  {
    return new self(
      $this->conn,
      table: $this->table,
      select: $this->select,
      where: $this->where,
      params: $this->params,
      order: $this->order,
      limit: $n,
      offset: $this->offset,
      forUpdate: $this->forUpdate,
      joins: $this->joins
    );
  }

  public function transaction(callable $cb): mixed
  {
    return $cb($this);
  }

  public function beginTransaction(): self
  {
    $this->conn->beginTransaction();
    return $this;
  }

  public function inTransaction(): bool
  {
    return $this->conn->getPdo()->inTransaction();
  }

  public function commit(): self
  {
    $this->conn->commit();
    return $this;
  }

  public function rollback(): self
  {
    $this->conn->rollBack();
    return $this;
  }

  public function getConnection(): DatabaseConnectionInterface
  {
    return $this->conn;
  }


  public function createTableFromAttributes(string $t, array $c, array $i = []): string
  {
    return '';
  }

  public function createTable(string $n, array $c, bool $i = false): string
  {
    $driver = $this->conn->getDriver();
    $sql = $this->buildCreateTableSql($n, $c, $i, $driver);
    $this->lastSql = $sql;
    return $sql;
  }

  public function createIndex(string $n, array $c, bool $u = false): string
  {
    return '';
  }

  public function dropTable(string $n): string
  {
    $driver = $this->conn->getDriver();
    $sql = $this->buildDropTableSql($n, $driver);
    $this->lastSql = $sql;
    return $sql;
  }

  public function getSql(): string
  {
    return $this->lastSql;
  }

  /**
   * Build CREATE TABLE SQL based on database driver
   */
  private function buildCreateTableSql(string $tableName, array $columns, bool $ifNotExists, string $driver): string
  {
    $identifierQuote = $this->getIdentifierQuote($driver);
    $quotedTableName = $identifierQuote . $tableName . $identifierQuote;

    $columnDefinitions = [];
    foreach ($columns as $columnName => $columnDef) {
      $quotedColumnName = $identifierQuote . $columnName . $identifierQuote;
      $normalizedDef = $this->normalizeColumnDefinition($columnDef, $driver);
      $columnDefinitions[] = $quotedColumnName . ' ' . $normalizedDef;
    }

    $columnsSql = implode(",\n    ", $columnDefinitions);
    $ifNotExistsClause = $ifNotExists ? ' IF NOT EXISTS' : '';

    $sql = "CREATE TABLE{$ifNotExistsClause} {$quotedTableName} (\n    {$columnsSql}\n)";

    if ($driver === 'mysql') {
      $sql .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    return $sql;
  }

  /**
   * Build DROP TABLE SQL based on database driver
   */
  private function buildDropTableSql(string $tableName, string $driver): string
  {
    $identifierQuote = $this->getIdentifierQuote($driver);
    $quotedTableName = $identifierQuote . $tableName . $identifierQuote;
    return "DROP TABLE {$quotedTableName}";
  }

  /**
   * Get identifier quote character based on driver
   */
  private function getIdentifierQuote(string $driver): string
  {
    return match ($driver) {
      'mysql' => '`',
      'sqlite', 'pgsql' => '"',
      default => '"',
    };
  }

  /**
   * Normalize column definition based on database driver
   */
  private function normalizeColumnDefinition(string $definition, string $driver): string
  {
    $definition = trim($definition);

    if ($driver === 'pgsql') {
      if (preg_match('/\bINTEGER\b/i', $definition) && preg_match('/\b(?:AUTO_INCREMENT|AUTOINCREMENT)\b/i', $definition)) {
        $definition = preg_replace('/\bINTEGER\b/i', 'SERIAL', $definition);
        $definition = preg_replace('/\s+(?:AUTO_INCREMENT|AUTOINCREMENT)\b/i', '', $definition);
      } elseif (preg_match('/\bBIGINT\b/i', $definition) && preg_match('/\b(?:AUTO_INCREMENT|AUTOINCREMENT)\b/i', $definition)) {
        $definition = preg_replace('/\bBIGINT\b/i', 'BIGSERIAL', $definition);
        $definition = preg_replace('/\s+(?:AUTO_INCREMENT|AUTOINCREMENT)\b/i', '', $definition);
      }
    } elseif ($driver === 'mysql') {
      $definition = preg_replace('/\bAUTOINCREMENT\b/i', 'AUTO_INCREMENT', $definition);
      if (preg_match('/\bINTEGER\b(?!\s+(?:UNSIGNED|ZEROFILL))/i', $definition)) {
        $definition = preg_replace('/\bINTEGER\b/i', 'INT', $definition);
      }
    } elseif ($driver === 'sqlite') {
      $definition = preg_replace('/\bAUTO_INCREMENT\b/i', 'AUTOINCREMENT', $definition);
    }

    return $definition;
  }

  public function setTable(string $table): QueryBuilderInterface
  {
    return new self(
      $this->conn,
      table: $table,
      select: $this->select,
      where: $this->where,
      params: $this->params,
      order: $this->order,
      limit: $this->limit,
      offset: $this->offset,
      forUpdate: $this->forUpdate,
      joins: $this->joins
    );
  }

  public function find(int $id): ?array
  {
    // TODO: Implement find() method.
    return [];
  }
}
