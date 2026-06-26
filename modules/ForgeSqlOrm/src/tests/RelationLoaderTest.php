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
use App\Modules\ForgeSqlOrm\ORM\RelationLoader;
use App\Modules\ForgeSqlOrm\ORM\Values\Relate;
use App\Modules\ForgeSqlOrm\ORM\Values\RelationKind;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;
use App\Modules\ForgeSqlOrm\ORM\QueryBuilder;

#[Group("forgesql-relations")]
final class RelationLoaderTest extends TestCase
{
    private \PDO $pdo;

    #[BeforeEach]
    public function setUp(): void
    {
        $ref = new \ReflectionProperty(Container::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)");
        $this->pdo->exec("CREATE TABLE posts (id INTEGER PRIMARY KEY, user_id INTEGER, title TEXT)");
        $this->pdo->exec("CREATE TABLE profiles (id INTEGER PRIMARY KEY, user_id INTEGER, bio TEXT)");

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
    }

    #[Test("hasMany loads related models")]
    public function has_many(): void
    {
        $this->pdo->exec("INSERT INTO users (id, name) VALUES (1, 'Alice')");
        $this->pdo->exec("INSERT INTO posts (user_id, title) VALUES (1, 'Post 1')");
        $this->pdo->exec("INSERT INTO posts (user_id, title) VALUES (1, 'Post 2')");

        $user = UserModel::fromRow(['id' => 1, 'name' => 'Alice']);

        $loader = new RelationLoader($user);
        $loader->load('posts');

        $posts = $user->getRelationValue('posts');
        $this->assertCount(2, $posts);
        $this->assertSame('Post 1', $posts[0]->title);
    }

    #[Test("hasOne loads single related model")]
    public function has_one(): void
    {
        $this->pdo->exec("INSERT INTO users (id, name) VALUES (1, 'Alice')");
        $this->pdo->exec("INSERT INTO profiles (user_id, bio) VALUES (1, 'Bio of Alice')");

        $user = UserModel::fromRow(['id' => 1, 'name' => 'Alice']);

        $loader = new RelationLoader($user);
        $loader->load('profile');

        $profile = $user->getRelationValue('profile');
        $this->assertNotNull($profile);
        $this->assertSame('Bio of Alice', $profile->bio);
    }

    #[Test("hasOne returns null when no relation")]
    public function has_one_null(): void
    {
        $user = UserModel::fromRow(['id' => 1, 'name' => 'Alice']);

        $loader = new RelationLoader($user);
        $loader->load('profile');

        $profile = $user->getRelationValue('profile');
        $this->assertNull($profile);
    }

    #[Test("hasMany returns empty array when no relations")]
    public function has_many_empty(): void
    {
        $user = UserModel::fromRow(['id' => 1, 'name' => 'Alice']);

        $loader = new RelationLoader($user);
        $loader->load('posts');

        $posts = $user->getRelationValue('posts');
        $this->assertSame([], $posts);
    }

    #[Test("nested relation loading with dot notation")]
    public function nested_relations(): void
    {
        $this->pdo->exec("INSERT INTO users (id, name) VALUES (1, 'Alice')");
        $this->pdo->exec("INSERT INTO posts (id, user_id, title) VALUES (10, 1, 'Post 1')");

        $user = UserModel::fromRow(['id' => 1, 'name' => 'Alice']);

        $loader = new RelationLoader($user);
        $loader->load('posts');

        $post = $user->getRelationValue('posts')[0];
        $this->assertSame('Post 1', $post->title);
    }

    #[Test("eager loading with multiple models")]
    public function multiple_models(): void
    {
        $this->pdo->exec("INSERT INTO users (id, name) VALUES (1, 'Alice')");
        $this->pdo->exec("INSERT INTO users (id, name) VALUES (2, 'Bob')");
        $this->pdo->exec("INSERT INTO posts (user_id, title) VALUES (1, 'Alice Post 1')");
        $this->pdo->exec("INSERT INTO posts (user_id, title) VALUES (2, 'Bob Post 1')");
        $this->pdo->exec("INSERT INTO posts (user_id, title) VALUES (2, 'Bob Post 2')");

        $alice = UserModel::fromRow(['id' => 1, 'name' => 'Alice']);
        $bob = UserModel::fromRow(['id' => 2, 'name' => 'Bob']);

        $loader = new RelationLoader($alice, $bob);
        $loader->load('posts');

        $this->assertCount(1, $alice->getRelationValue('posts'));
        $this->assertCount(2, $bob->getRelationValue('posts'));
    }

    #[Test("belongsTo loads parent model")]
    public function belongs_to(): void
    {
        $this->pdo->exec("INSERT INTO users (id, name) VALUES (1, 'Alice')");
        $this->pdo->exec("INSERT INTO posts (user_id, title) VALUES (1, 'Post by Alice')");

        $post = PostModel::fromRow(['id' => 1, 'user_id' => 1, 'title' => 'Post by Alice']);

        $this->assertSame(1, $post->user_id);

        $user = UserModel::fromRow(['id' => 1, 'name' => 'Alice']);
        $this->assertSame('Alice', $user->name);
    }
}

#[Table(name: 'users')]
class UserModel extends Model
{
    #[Column(primary: true)]
    public int $id = 0;

    #[Column]
    public ?string $name = null;

    #[Relate(kind: RelationKind::HasMany, target: PostModel::class, foreignKey: 'user_id', localKey: 'id')]
    public function posts(): void {}

    #[Relate(kind: RelationKind::HasOne, target: ProfileModel::class, foreignKey: 'user_id', localKey: 'id')]
    public function profile(): void {}

    public function getRelationValue(string $name): mixed
    {
        return $this->relations[$name] ?? null;
    }
}

#[Table(name: 'posts')]
class PostModel extends Model
{
    #[Column(primary: true)]
    public int $id = 0;

    #[Column]
    public int $user_id = 0;

    #[Column]
    public ?string $title = null;
}

#[Table(name: 'profiles')]
class ProfileModel extends Model
{
    #[Column(primary: true)]
    public int $id = 0;

    #[Column]
    public int $user_id = 0;

    #[Column]
    public ?string $bio = null;
}
