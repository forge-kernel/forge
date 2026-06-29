<?php

declare(strict_types=1);

namespace App\Http\Examples;

use Modules\ForgeMultiTenant\Attributes\TenantScope;
use Modules\ForgeSqlOrm\ORM\QueryBuilder;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Forge\Traits\SecurityHelper;
use Exception;

#[Routable(prefix: '/examples')]
#[TenantScope("central")]
#[UseMiddleware("web")]
final class Sql
{
    use ResponseHelper;
    use SecurityHelper;

    public function __construct(
        public readonly QueryBuilder $builder,
        public readonly DatabaseConnectionInterface $connection,
    ) {
    }

    #[Endpoint("/raw-sql")]
    public function rawSqlExamples(): Response
    {
        $examples = [];

        $examples['forge_database_sql'] = [
            'exec_example' => $this->forgeDatabaseSQLExecExample(),
            'query_example' => $this->forgeDatabaseSQLQueryExample(),
            'prepare_example' => $this->forgeDatabaseSQLPrepareExample(),
        ];

        $examples['forge_sql_orm'] = [
            'raw_example' => $this->forgeSqlOrmRawExample(),
            'whereRaw_example' => $this->forgeSqlOrmWhereRawExample(),
            'combined_example' => $this->forgeSqlOrmCombinedExample(),
        ];

        $examples['transactions'] = [
            'forge_database_sql_commit' => $this->forgeDatabaseSQLTransactionCommit(),
            'forge_database_sql_rollback' => $this->forgeDatabaseSQLTransactionRollback(),
            'forge_sql_orm_commit' => $this->forgeSqlOrmTransactionCommit(),
            'forge_sql_orm_rollback' => $this->forgeSqlOrmTransactionRollback(),
        ];

        return $this->jsonResponse($examples);
    }

    private function forgeDatabaseSQLExecExample(): array
    {
        $this->connection->exec("CREATE TABLE IF NOT EXISTS example_table (id INTEGER PRIMARY KEY, name TEXT)");
        return ['status' => 'exec executed'];
    }

    private function forgeDatabaseSQLQueryExample(): array
    {
        $stmt = $this->connection->query("SELECT * FROM users LIMIT 5");
        return $stmt->fetchAll();
    }

    private function forgeDatabaseSQLPrepareExample(): array
    {
        $stmt = $this->connection->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => 1]);
        return $stmt->fetchAll();
    }

    private function forgeSqlOrmRawExample(): array
    {
        $results = $this->builder->raw("SELECT * FROM users WHERE status = :status", [':status' => 'active']);
        return $results;
    }

    private function forgeSqlOrmWhereRawExample(): array
    {
        $results = $this->builder
            ->table('users')
            ->whereRaw('status = :status', [':status' => 'active'])
            ->get();
        return $results;
    }

    private function forgeSqlOrmCombinedExample(): array
    {
        $results = $this->builder
            ->table('users')
            ->select('id', 'email', 'identifier')
            ->where('status', '=', 'active')
            ->whereRaw('identifier IS NOT NULL', [])
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();
        return $results;
    }

    private function forgeDatabaseSQLTransactionCommit(): array
    {
        $this->connection->beginTransaction();
        try {
            $stmt = $this->connection->prepare("INSERT INTO example_table (name) VALUES (:name)");
            $stmt->execute([':name' => 'transaction_test_commit']);
            $this->connection->commit();
            return ['status' => 'committed', 'message' => 'Transaction committed successfully'];
        } catch (Exception $e) {
            $this->connection->rollBack();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function forgeDatabaseSQLTransactionRollback(): array
    {
        $checkBeforeStmt = $this->connection->prepare("SELECT COUNT(*) as count FROM example_table WHERE name = :name");
        $checkBeforeStmt->execute([':name' => 'transaction_test_rollback']);
        $beforeResult = $checkBeforeStmt->fetch();
        $countBefore = (int) $beforeResult['count'];

        $this->connection->beginTransaction();
        try {
            $stmt = $this->connection->prepare("INSERT INTO example_table (name) VALUES (:name)");
            $stmt->execute([':name' => 'transaction_test_rollback']);
            throw new Exception('Simulated error to trigger rollback');
        } catch (Exception $e) {
            $this->connection->rollBack();
            $checkAfterStmt = $this->connection->prepare("SELECT COUNT(*) as count FROM example_table WHERE name = :name");
            $checkAfterStmt->execute([':name' => 'transaction_test_rollback']);
            $afterResult = $checkAfterStmt->fetch();
            $countAfter = (int) $afterResult['count'];
            return [
                'status' => 'rolled_back',
                'message' => 'Transaction rolled back successfully',
                'count_before' => $countBefore,
                'count_after' => $countAfter,
                'record_exists' => $countAfter > $countBefore,
                'rollback_worked' => $countAfter == $countBefore
            ];
        }
    }

    private function forgeSqlOrmTransactionCommit(): array
    {
        try {
            $this->builder->beginTransaction();
            $id = $this->builder->table('example_table')->insert(['name' => 'orm_transaction_commit']);
            $this->builder->commit();
            return ['status' => 'committed', 'inserted_id' => $id];
        } catch (Exception $e) {
            $this->builder->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function forgeSqlOrmTransactionRollback(): array
    {
        $countBefore = $this->builder
            ->table('example_table')
            ->where('name', '=', 'orm_transaction_rollback')
            ->count();

        try {
            $this->builder->beginTransaction();
            $this->builder->table('example_table')->insert(['name' => 'orm_transaction_rollback']);
            throw new Exception('Simulated error to trigger rollback');
            $this->builder->commit();
        } catch (Exception $e) {
            $this->builder->rollback();
            $countAfter = $this->builder
                ->table('example_table')
                ->where('name', '=', 'orm_transaction_rollback')
                ->count();
            return [
                'status' => 'rolled_back',
                'message' => 'Transaction rolled back successfully',
                'count_before' => $countBefore,
                'count_after' => $countAfter,
                'record_exists' => $countAfter > $countBefore,
                'rollback_worked' => $countAfter == $countBefore
            ];
        }
    }
}
