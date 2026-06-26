<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\Tests;

use App\Modules\ForgeTesting\Attributes\BeforeEach;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use App\Modules\ForgeSqlOrm\ORM\Attributes\Column;
use App\Modules\ForgeSqlOrm\ORM\Attributes\Table;
use App\Modules\ForgeSqlOrm\ORM\Cache\QueryCache;
use App\Modules\ForgeSqlOrm\ORM\Model;
use App\Modules\ForgeSqlOrm\ORM\RecordRepository;
use App\Modules\ForgeSqlOrm\ORM\Repository;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;
use App\Modules\ForgeSqlOrm\ORM\QueryBuilder;

#[Group("forgesql-repository")]
final class RecordRepositoryTest extends TestCase
{
    private \PDO $pdo;
    private QueryCache $cache;
    private RecordRepository $repo;

    #[BeforeEach]
    public function setUp(): void
    {
        $ref = new \ReflectionProperty(Container::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("CREATE TABLE repo_stubs (id INTEGER PRIMARY KEY, name TEXT, age INTEGER, created_at TEXT)");

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

        $this->cache = new QueryCache(3600);
        $this->repo = new class($this->cache) extends RecordRepository {
            protected function getModelClass(): string { return RepoStubModel::class; }
        };
    }

    #[Test("create persists and returns model")]
    public function create(): void
    {
        $model = $this->repo->create(['name' => 'Alice', 'age' => 30]);
        $this->assertInstanceOf(RepoStubModel::class, $model);
        $this->assertNotNull($model->id);
        $this->assertSame('Alice', $model->name);
    }

    #[Test("create ignores unknown properties")]
    public function create_ignores_unknown(): void
    {
        $model = $this->repo->create(['name' => 'Alice', 'unknown_field' => 'should be ignored']);
        $this->assertInstanceOf(RepoStubModel::class, $model);
        $this->assertSame('Alice', $model->name);
    }

    #[Test("find returns model from cache")]
    public function find_returns_cached(): void
    {
        $created = $this->repo->create(['name' => 'Alice', 'age' => 30]);

        $found1 = $this->repo->find($created->id);
        $this->assertSame('Alice', $found1->name);

        $found2 = $this->repo->find($created->id);
        $this->assertSame('Alice', $found2->name);
    }

    #[Test("find returns cloned model to prevent cache mutation")]
    public function find_returns_clone(): void
    {
        $created = $this->repo->create(['name' => 'Alice', 'age' => 30]);

        $found1 = $this->repo->find($created->id);
        $found1->name = 'MUTATED';

        $found2 = $this->repo->find($created->id);
        $this->assertSame('Alice', $found2->name);
    }

    #[Test("find returns null for missing id")]
    public function find_missing(): void
    {
        $this->assertNull($this->repo->find(999));
    }

    #[Test("findBy returns model")]
    public function find_by(): void
    {
        $this->repo->create(['name' => 'Alice', 'age' => 30]);
        $this->repo->create(['name' => 'Bob', 'age' => 25]);

        $found = $this->repo->findBy('name', 'Bob');
        $this->assertNotNull($found);
        $this->assertSame('Bob', $found->name);
    }

    #[Test("findBy returns null when no match")]
    public function find_by_missing(): void
    {
        $this->assertNull($this->repo->findBy('name', 'Nobody'));
    }

    #[Test("findAll returns all records")]
    public function find_all(): void
    {
        $this->repo->create(['name' => 'Alice']);
        $this->repo->create(['name' => 'Bob']);

        $all = $this->repo->findAll();
        $this->assertCount(2, $all);
    }

    #[Test("update modifies existing record")]
    public function update_record(): void
    {
        $model = $this->repo->create(['name' => 'Alice', 'age' => 30]);
        $result = $this->repo->update($model, ['name' => 'Alice Updated']);
        $this->assertTrue($result);

        $found = $this->repo->find($model->id);
        $this->assertSame('Alice Updated', $found->name);
    }

    #[Test("delete removes record")]
    public function delete_record(): void
    {
        $model = $this->repo->create(['name' => 'Alice']);
        $result = $this->repo->delete($model->id);
        $this->assertTrue($result);
        $this->assertNull($this->repo->find($model->id));
    }

    #[Test("delete returns false for missing record")]
    public function delete_missing(): void
    {
        $result = $this->repo->delete(999);
        $this->assertFalse($result);
    }

    #[Test("createMany inserts multiple records in transaction")]
    public function create_many(): void
    {
        $records = $this->repo->createMany([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
        ]);

        $this->assertCount(2, $records);
        $this->assertCount(2, $this->repo->findAll());
    }

    #[Test("paginate returns paginated results")]
    public function paginate(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->pdo->exec("INSERT INTO repo_stubs (name, age) VALUES ('User$i', $i)");
        }

        $result = $this->repo->paginate(page: 1, perPage: 3);
        $this->assertCount(3, $result->items());
        $this->assertSame(10, $result->total());
        $this->assertSame(1, $result->currentPage());
    }
}

#[Table(name: 'repo_stubs')]
class RepoStubModel extends Model
{
    #[Column(primary: true)]
    public int $id = 0;

    #[Column]
    public ?string $name = null;

    #[Column]
    public ?int $age = null;
}
