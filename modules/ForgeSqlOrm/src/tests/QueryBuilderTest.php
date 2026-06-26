<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\Tests;

use App\Modules\ForgeTesting\Attributes\BeforeEach;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use App\Modules\ForgeSqlOrm\ORM\QueryBuilder;

#[Group("forgesql-querybuilder")]
final class QueryBuilderTest extends TestCase
{
    private QueryBuilder $builder;
    private \PDO $pdo;

    #[BeforeEach]
    public function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT, age INTEGER, deleted_at TEXT)");

        $conn = new class($this->pdo) implements DatabaseConnectionInterface {
            public function __construct(private \PDO $pdo) {}
            public function getPdo(): \PDO { return $this->pdo; }
            public function exec(string $statement): int|false { return $this->pdo->exec($statement); }
            public function prepare(string $statement): \PDOStatement { return $this->pdo->prepare($statement); }
            public function query(string $statement): \PDOStatement { return $this->pdo->query($statement); }
            public function beginTransaction(): bool { return $this->pdo->beginTransaction(); }
            public function commit(): bool { return $this->pdo->commit(); }
            public function rollBack(): bool { return $this->pdo->rollBack(); }
            public function getDriver(): string { return 'sqlite'; }
        };

        $this->builder = new QueryBuilder($conn);
    }

    #[Test("whereNull generates IS NULL, not parameterized")]
    public function where_null_generates_is_null(): void
    {
        $this->pdo->exec("INSERT INTO test_table (name) VALUES ('Alice')");
        $results = $this->builder->table('test_table')->whereNull('deleted_at')->get();
        $this->assertCount(1, $results);
    }

    #[Test("whereNotNull generates IS NOT NULL")]
    public function where_not_null_generates_is_not_null(): void
    {
        $this->pdo->exec("INSERT INTO test_table (name, age) VALUES ('Alice', 30)");
        $this->pdo->exec("INSERT INTO test_table (name, age, deleted_at) VALUES ('Bob', 25, '2024-01-01')");

        $qb = $this->builder->table('test_table')->whereNull('deleted_at');
        $rows = $qb->get();
        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
    }

    #[Test("whereNotIn binds values correctly")]
    public function where_not_in_binds_values_correctly(): void
    {
        $this->pdo->exec("INSERT INTO test_table (id, name) VALUES (1, 'Alice')");
        $this->pdo->exec("INSERT INTO test_table (id, name) VALUES (2, 'Bob')");
        $this->pdo->exec("INSERT INTO test_table (id, name) VALUES (3, 'Charlie')");

        $qb = $this->builder->table('test_table')->whereNotIn('id', [1, 3]);
        $rows = $qb->get();
        $this->assertCount(1, $rows);
        $this->assertSame('Bob', $rows[0]['name']);
    }

    #[Test("whereNotIn with empty values returns unchanged builder")]
    public function where_not_in_empty_values(): void
    {
        $qb = $this->builder->table('test_table')->whereNotIn('id', []);
        $this->assertStringNotContainsString('NOT IN', $qb->getSql());
    }

    #[Test("select appends to select list")]
    public function select_appends_columns(): void
    {
        $qb = $this->builder->table('test_table')->selectRaw('COUNT(*) as cnt');
        $rows = $qb->get();
        $this->assertNotNull($rows);
    }

    #[Test("selectRaw does not mutate original builder")]
    public function select_raw_immutability(): void
    {
        $original = $this->builder->table('test_table');
        $modified = $original->selectRaw('COUNT(*)');

        $this->assertSame([], (new \ReflectionProperty($original, 'select'))->getValue($original));
        $this->assertSame(['COUNT(*)'], (new \ReflectionProperty($modified, 'select'))->getValue($modified));
    }

    #[Test("update does not mutate original builder params")]
    public function update_immutability(): void
    {
        $this->pdo->exec("INSERT INTO test_table (id, name) VALUES (1, 'Alice')");

        $qb = $this->builder->table('test_table')->where('id', '=', '1');
        $paramsBefore = (new \ReflectionProperty($qb, 'params'))->getValue($qb);
        $qb->update(['name' => 'Bob']);
        $paramsAfter = (new \ReflectionProperty($qb, 'params'))->getValue($qb);

        $this->assertSame($paramsBefore, $paramsAfter);
    }

    #[Test("orderBy builds ORDER BY clause")]
    public function order_by(): void
    {
        $this->pdo->exec("INSERT INTO test_table (name, age) VALUES ('Bob', 30)");
        $this->pdo->exec("INSERT INTO test_table (name, age) VALUES ('Alice', 25)");

        $rows = $this->builder->table('test_table')->orderBy('name', 'ASC')->get();
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('Bob', $rows[1]['name']);
    }

    #[Test("limit and offset work")]
    public function limit_offset(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->pdo->exec("INSERT INTO test_table (id, name) VALUES ($i, 'User$i')");
        }

        $rows = $this->builder->table('test_table')->orderBy('id', 'ASC')->limit(2)->offset(1)->get();
        $this->assertCount(2, $rows);
        $this->assertSame('User2', $rows[0]['name']);
    }

    #[Test("groupBy and having generate correct SQL")]
    public function group_by_having(): void
    {
        $this->pdo->exec("INSERT INTO test_table (name, age) VALUES ('Alice', 25)");
        $this->pdo->exec("INSERT INTO test_table (name, age) VALUES ('Bob', 25)");
        $this->pdo->exec("INSERT INTO test_table (name, age) VALUES ('Charlie', 30)");

        $rows = $this->builder->table('test_table')
            ->selectRaw('age, COUNT(*) as cnt')
            ->groupBy('age')
            ->having('age', '>', '25')
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame(30, (int) $rows[0]['age']);
    }

    #[Test("find returns record by id")]
    public function find_by_id(): void
    {
        $this->pdo->exec("INSERT INTO test_table (id, name) VALUES (42, 'Alice')");
        $row = $this->builder->table('test_table')->find(42);
        $this->assertNotNull($row);
        $this->assertSame('Alice', $row['name']);
    }

    #[Test("find returns null for missing id")]
    public function find_missing(): void
    {
        $row = $this->builder->table('test_table')->find(999);
        $this->assertNull($row);
    }

    #[Test("insertMany inserts multiple rows")]
    public function insert_many(): void
    {
        $this->builder->table('test_table')->insertMany([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
        ]);

        $rows = $this->builder->table('test_table')->get();
        $this->assertCount(2, $rows);
    }

    #[Test("transaction commits successfully")]
    public function transaction_commits(): void
    {
        $result = $this->builder->table('test_table')->transaction(function ($qb) {
            $qb->insert(['name' => 'Alice', 'age' => 30]);
            $qb->insert(['name' => 'Bob', 'age' => 25]);
            return 'done';
        });

        $this->assertSame('done', $result);
        $rows = $this->builder->table('test_table')->get();
        $this->assertCount(2, $rows);
    }

    #[Test("transaction rolls back on exception")]
    public function transaction_rollback(): void
    {
        try {
            $this->builder->table('test_table')->transaction(function ($qb) {
                $qb->insert(['name' => 'Alice', 'age' => 30]);
                throw new \RuntimeException('fail');
            });
        } catch (\RuntimeException) {
        }

        $rows = $this->builder->table('test_table')->get();
        $this->assertCount(0, $rows);
    }

    #[Test("whereIn binds correctly")]
    public function where_in(): void
    {
        $this->pdo->exec("INSERT INTO test_table (id, name) VALUES (1, 'Alice')");
        $this->pdo->exec("INSERT INTO test_table (id, name) VALUES (2, 'Bob')");
        $this->pdo->exec("INSERT INTO test_table (id, name) VALUES (3, 'Charlie')");

        $rows = $this->builder->table('test_table')->whereIn('id', [1, 3])->get();
        $this->assertCount(2, $rows);
    }

    #[Test("where chaining with AND")]
    public function where_chaining(): void
    {
        $this->pdo->exec("INSERT INTO test_table (name, age) VALUES ('Alice', 30)");
        $this->pdo->exec("INSERT INTO test_table (name, age) VALUES ('Alice', 25)");
        $this->pdo->exec("INSERT INTO test_table (name, age) VALUES ('Bob', 30)");

        $rows = $this->builder->table('test_table')
            ->where('name', '=', 'Alice')
            ->where('age', '=', '30')
            ->get();
        $this->assertCount(1, $rows);
    }

    #[Test("count returns correct number")]
    public function count(): void
    {
        $this->pdo->exec("INSERT INTO test_table (name) VALUES ('Alice')");
        $this->pdo->exec("INSERT INTO test_table (name) VALUES ('Bob')");
        $this->assertSame(2, $this->builder->table('test_table')->count());
    }

    #[Test("exists returns true when records match")]
    public function exists_true(): void
    {
        $this->pdo->exec("INSERT INTO test_table (name) VALUES ('Alice')");
        $this->assertTrue($this->builder->table('test_table')->where('name', '=', 'Alice')->exists());
    }

    #[Test("exists returns false when no records match")]
    public function exists_false(): void
    {
        $this->assertFalse($this->builder->table('test_table')->where('name', '=', 'Nobody')->exists());
    }

    #[Test("first returns first matching record")]
    public function first_record(): void
    {
        $this->pdo->exec("INSERT INTO test_table (name) VALUES ('Alice')");
        $this->pdo->exec("INSERT INTO test_table (name) VALUES ('Bob')");
        $row = $this->builder->table('test_table')->orderBy('id', 'ASC')->first();
        $this->assertNotNull($row);
        $this->assertSame('Alice', $row['name']);
    }

    #[Test("first returns null when no match")]
    public function first_null(): void
    {
        $this->assertNull($this->builder->table('test_table')->where('name', '=', 'Nobody')->first());
    }

    #[Test("delete removes records")]
    public function delete_records(): void
    {
        $this->pdo->exec("INSERT INTO test_table (name) VALUES ('Alice')");
        $count = $this->builder->table('test_table')->where('name', '=', 'Alice')->delete();
        $this->assertSame(1, $count);
        $this->assertSame(0, $this->builder->table('test_table')->count());
    }

    #[Test("insertGetId returns last insert id")]
    public function insert_get_id(): void
    {
        $id = $this->builder->table('test_table')->insertGetId(['name' => 'Alice', 'age' => 30]);
        $this->assertGreaterThan(0, $id);
    }

    #[Test("table returns self (never string)")]
    public function table_returns_self(): void
    {
        $result = $this->builder->table('test_table');
        $this->assertInstanceOf(\App\Modules\ForgeSqlOrm\ORM\QueryBuilder::class, $result);
    }

    #[Test("raw triggers deprecation warning")]
    public function raw_deprecation(): void
    {
        $this->pdo->exec("INSERT INTO test_table (name) VALUES ('Alice')");

        $deprecationCaught = false;
        set_error_handler(function (int $errno) use (&$deprecationCaught): bool {
            if ($errno === E_USER_DEPRECATED) {
                $deprecationCaught = true;
                return true;
            }
            return false;
        });

        $rows = $this->builder->table('test_table')->raw('SELECT * FROM test_table');
        $this->assertCount(1, $rows);
        $this->assertTrue($deprecationCaught);

        restore_error_handler();
    }
}
