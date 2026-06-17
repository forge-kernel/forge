<?php
declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Seeders;

use App\Modules\ForgeDatabaseSQL\DB\Seeders\Attributes\AutoRollback;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use ReflectionClass;

abstract class Seeder
{
    public function __construct(protected DatabaseConnectionInterface $connection)
    {
    }

    abstract public function up(): void;

    public function down(): void
    {
        $reflection = $this->getReflection();
        $attribute = $reflection->getAttributes(AutoRollback::class)[0] ?? null;

        if ($attribute) {
            $data = $attribute->newInstance();
            $this->deleteWhere($data->table, $data->where);
        }
    }

    protected function getReflection(): ReflectionClass
    {
        static $cache = [];
        $key = get_class($this);

        if (!isset($cache[$key])) {
            $cache[$key] = new ReflectionClass($this);
        }

        return $cache[$key];
    }

    protected function deleteWhere(string $table, array $where): void
    {
        $clauses = [];
        $params = [];

        foreach ($where as $col => $val) {
            $clauses[] = "`$col` = ?";
            $params[] = $val;
        }

        $sql = sprintf("DELETE FROM %s WHERE %s", $table, implode(' AND ', $clauses));
        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute($params);
    }

    protected function insertBatch(string $table, array $rows, int $batchSize = 500): void
    {
        if (empty($rows)) return;

        $pdo = $this->connection->getPdo();
        $columns = array_keys($rows[0]);
        $colList = implode(',', array_map(fn($c) => "`$c`", $columns));
        $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';

        $chunks = array_chunk($rows, $batchSize);
        foreach ($chunks as $chunk) {
            $values = [];
            foreach ($chunk as $row) {
                $values = array_merge($values, array_values($row));
            }

            $stmt = $pdo->prepare("INSERT INTO {$table} ({$colList}) VALUES " .
                implode(',', array_fill(0, count($chunk), $placeholders)));
            $stmt->execute($values);
        }
    }
}
