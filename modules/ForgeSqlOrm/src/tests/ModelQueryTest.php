<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\Tests;

use App\Modules\ForgeTesting\Attributes\BeforeEach;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use App\Modules\ForgeSqlOrm\ORM\Attributes\Column;
use App\Modules\ForgeSqlOrm\ORM\Attributes\Table;
use App\Modules\ForgeSqlOrm\ORM\Model;
use App\Modules\ForgeSqlOrm\ORM\ModelQuery;
use App\Modules\ForgeSqlOrm\ORM\Traits\SoftDeletes;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;
use App\Modules\ForgeSqlOrm\ORM\QueryBuilder;

#[Group("forgesql-modelquery")]
final class ModelQueryTest extends TestCase
{
    private \PDO $pdo;
    private QueryBuilderInterface $builder;

    #[BeforeEach]
    public function setUp(): void
    {
        $ref = new \ReflectionProperty(Container::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("CREATE TABLE model_query_stubs (id INTEGER PRIMARY KEY, name TEXT, age INTEGER, created_at TEXT)");
        $this->pdo->exec("CREATE TABLE soft_delete_stubs (id INTEGER PRIMARY KEY, name TEXT, deleted_at TEXT)");

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

        $container = Container::getInstance();
        $container->bind(DatabaseConnectionInterface::class, fn() => $conn, singleton: true);
        $container->bind(QueryBuilderInterface::class, fn($c) => new QueryBuilder($c->get(DatabaseConnectionInterface::class)), singleton: true);

        $this->builder = $container->get(QueryBuilderInterface::class);
    }

    #[Test("get returns models from query")]
    public function get_returns_models(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (name, age) VALUES ('Alice', 30)");
        $this->pdo->exec("INSERT INTO model_query_stubs (name, age) VALUES ('Bob', 25)");

        $results = ModelQueryStub::query()->get();
        $this->assertCount(2, $results);
        $this->assertInstanceOf(ModelQueryStub::class, $results[0]);
    }

    #[Test("where filters results")]
    public function where_filters(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (name, age) VALUES ('Alice', 30)");
        $this->pdo->exec("INSERT INTO model_query_stubs (name, age) VALUES ('Bob', 25)");

        $results = ModelQueryStub::query()->where('name', '=', 'Alice')->get();
        $this->assertCount(1, $results);
        $this->assertSame('Alice', $results[0]->name);
    }

    #[Test("first returns first matching model")]
    public function first_returns_model(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (name, age) VALUES ('Alice', 30)");
        $model = ModelQueryStub::query()->first();
        $this->assertInstanceOf(ModelQueryStub::class, $model);
        $this->assertSame('Alice', $model->name);
    }

    #[Test("first returns null when no match")]
    public function first_null(): void
    {
        $model = ModelQueryStub::query()->where('name', '=', 'Nobody')->first();
        $this->assertNull($model);
    }

    #[Test("id filters by primary key")]
    public function id_filter(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (id, name) VALUES (5, 'Alice')");
        $model = ModelQueryStub::query()->id(5)->first();
        $this->assertNotNull($model);
        $this->assertSame('Alice', $model->name);
    }

    #[Test("orderBy sorts results")]
    public function order_by(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (name) VALUES ('Bob')");
        $this->pdo->exec("INSERT INTO model_query_stubs (name) VALUES ('Alice')");

        $results = ModelQueryStub::query()->orderBy('name', 'ASC')->get();
        $this->assertSame('Alice', $results[0]->name);
        $this->assertSame('Bob', $results[1]->name);
    }

    #[Test("limit restricts results")]
    public function limit(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (name) VALUES ('Alice')");
        $this->pdo->exec("INSERT INTO model_query_stubs (name) VALUES ('Bob')");
        $this->pdo->exec("INSERT INTO model_query_stubs (name) VALUES ('Charlie')");

        $results = ModelQueryStub::query()->limit(2)->get();
        $this->assertCount(2, $results);
    }

    #[Test("offset skips results")]
    public function offset(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->pdo->exec("INSERT INTO model_query_stubs (id, name) VALUES ($i, 'User$i')");
        }

        $results = ModelQueryStub::query()->orderBy('id', 'ASC')->offset(1)->get();
        $this->assertCount(2, $results);
        $this->assertSame('User2', $results[0]->name);
    }

    #[Test("whereNull filters null columns")]
    public function where_null(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (name, age) VALUES ('Alice', 30)");
        $this->pdo->exec("INSERT INTO model_query_stubs (name, age) VALUES ('Bob', NULL)");

        $results = ModelQueryStub::query()->whereNull('age')->get();
        $this->assertCount(1, $results);
        $this->assertSame('Bob', $results[0]->name);
    }

    #[Test("whereNotNull filters non-null columns")]
    public function where_not_null(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (name, age) VALUES ('Alice', 30)");
        $this->pdo->exec("INSERT INTO model_query_stubs (name, age) VALUES ('Bob', NULL)");

        $results = ModelQueryStub::query()->whereNotNull('age')->get();
        $this->assertCount(1, $results);
        $this->assertSame('Alice', $results[0]->name);
    }

    #[Test("whereIn filters by list")]
    public function where_in(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (id, name) VALUES (1, 'Alice')");
        $this->pdo->exec("INSERT INTO model_query_stubs (id, name) VALUES (2, 'Bob')");
        $this->pdo->exec("INSERT INTO model_query_stubs (id, name) VALUES (3, 'Charlie')");

        $results = ModelQueryStub::query()->whereIn('id', [1, 3])->get();
        $this->assertCount(2, $results);
    }

    #[Test("whereNotIn filters out list")]
    public function where_not_in(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (id, name) VALUES (1, 'Alice')");
        $this->pdo->exec("INSERT INTO model_query_stubs (id, name) VALUES (2, 'Bob')");
        $this->pdo->exec("INSERT INTO model_query_stubs (id, name) VALUES (3, 'Charlie')");

        $results = ModelQueryStub::query()->whereNotIn('id', [1, 3])->get();
        $this->assertCount(1, $results);
        $this->assertSame('Bob', $results[0]->name);
    }

    #[Test("insert returns last insert id")]
    public function insert(): void
    {
        $id = ModelQueryStub::query()->insert(['name' => 'Alice', 'age' => 30]);
        $this->assertGreaterThan(0, $id);
    }

    #[Test("update modifies records")]
    public function update(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (name, age) VALUES ('Alice', 30)");
        $count = ModelQueryStub::query()->where('name', '=', 'Alice')->update(['age' => 31]);
        $this->assertSame(1, $count);

        $model = ModelQueryStub::query()->where('name', '=', 'Alice')->first();
        $this->assertSame(31, $model->age);
    }

    #[Test("forceDelete removes records")]
    public function force_delete(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (name) VALUES ('Alice')");
        $count = ModelQueryStub::query()->where('name', '=', 'Alice')->forceDelete();
        $this->assertSame(1, $count);
        $this->assertSame(0, ModelQueryStub::query()->count());
    }

    #[Test("count returns correct total")]
    public function count(): void
    {
        $this->pdo->exec("INSERT INTO model_query_stubs (name) VALUES ('Alice')");
        $this->pdo->exec("INSERT INTO model_query_stubs (name) VALUES ('Bob')");
        $this->assertSame(2, ModelQueryStub::query()->count());
    }

    #[Test("withTrashed includes soft-deleted records")]
    public function with_trashed_includes_deleted(): void
    {
        $this->pdo->exec("INSERT INTO soft_delete_stubs (id, name, deleted_at) VALUES (1, 'Alice', NULL)");
        $this->pdo->exec("INSERT INTO soft_delete_stubs (id, name, deleted_at) VALUES (2, 'Bob', '2024-01-15 10:00:00')");

        $active = SoftDeleteStub::query()->get();
        $this->assertCount(1, $active);
        $this->assertSame('Alice', $active[0]->name);

        $all = SoftDeleteStub::query()->withTrashed()->get();
        $this->assertCount(2, $all);
    }

    #[Test("onlyTrashed returns only soft-deleted records")]
    public function only_trashed(): void
    {
        $this->pdo->exec("INSERT INTO soft_delete_stubs (id, name, deleted_at) VALUES (1, 'Alice', NULL)");
        $this->pdo->exec("INSERT INTO soft_delete_stubs (id, name, deleted_at) VALUES (2, 'Bob', '2024-01-15 10:00:00')");

        $trashed = SoftDeleteStub::query()->onlyTrashed()->get();
        $this->assertCount(1, $trashed);
        $this->assertSame('Bob', $trashed[0]->name);
    }

    #[Test("soft delete filter applies to first()")]
    public function soft_delete_filter_first(): void
    {
        $this->pdo->exec("INSERT INTO soft_delete_stubs (id, name, deleted_at) VALUES (1, 'Alice', NULL)");
        $this->pdo->exec("INSERT INTO soft_delete_stubs (id, name, deleted_at) VALUES (2, 'Bob', '2024-01-15 10:00:00')");

        $first = SoftDeleteStub::query()->first();
        $this->assertNotNull($first);
        $this->assertSame('Alice', $first->name);
    }

    #[Test("soft delete filter applies to count()")]
    public function soft_delete_filter_count(): void
    {
        $this->pdo->exec("INSERT INTO soft_delete_stubs (id, name, deleted_at) VALUES (1, 'Alice', NULL)");
        $this->pdo->exec("INSERT INTO soft_delete_stubs (id, name, deleted_at) VALUES (2, 'Bob', '2024-01-15 10:00:00')");

        $this->assertSame(1, SoftDeleteStub::query()->count());
        $this->assertSame(2, SoftDeleteStub::query()->withTrashed()->count());
    }
}

#[Table(name: 'model_query_stubs')]
class ModelQueryStub extends Model
{
    #[Column(primary: true)]
    public int $id = 0;

    #[Column]
    public ?string $name = null;

    #[Column]
    public ?int $age = null;
}

#[Table(name: 'soft_delete_stubs')]
class SoftDeleteStub extends Model
{
    use SoftDeletes;

    #[Column(primary: true)]
    public int $id = 0;

    #[Column]
    public ?string $name = null;
}
