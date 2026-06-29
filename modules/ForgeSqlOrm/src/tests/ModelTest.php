<?php
declare(strict_types=1);

namespace Modules\ForgeSqlOrm\Tests;

use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Modules\ForgeSqlOrm\ORM\Attributes\Column;
use Modules\ForgeSqlOrm\ORM\Attributes\Hidden;
use Modules\ForgeSqlOrm\ORM\Attributes\ProtectedFields;
use Modules\ForgeSqlOrm\ORM\Attributes\Table;
use Modules\ForgeSqlOrm\ORM\Model;
use Modules\ForgeSqlOrm\ORM\Traits\SoftDeletes;
use Modules\ForgeSqlOrm\ORM\Values\Cast;
use DateTimeImmutable;

#[Group("forgesql-model")]
final class ModelTest extends TestCase
{
    #[Test("fromRow populates properties from array")]
    public function from_row_populates_properties(): void
    {
        $model = ModelStub::fromRow(['id' => 1, 'name' => 'Alice', 'age' => 30]);
        $this->assertSame(1, $model->id);
        $this->assertSame('Alice', $model->name);
        $this->assertSame(30, $model->age);
    }

    #[Test("fromRow applies cast")]
    public function from_row_applies_cast(): void
    {
        $model = ModelStub::fromRow(['id' => 1, 'name' => 'Alice', 'age' => '30']);
        $this->assertSame(30, $model->age);
    }

    #[Test("fromRow parses datetime cast")]
    public function from_row_datetime_cast(): void
    {
        $model = ModelWithDates::fromRow(['id' => 1, 'created_at' => '2024-01-15 10:30:00']);
        $this->assertInstanceOf(DateTimeImmutable::class, $model->created_at);
        $this->assertSame('2024-01-15 10:30:00', $model->created_at->format('Y-m-d H:i:s'));
    }

    #[Test("fromRow handles hidden columns")]
    public function from_row_hidden_columns(): void
    {
        $model = ModelWithHidden::fromRow(['id' => 1, 'secret' => 'hidden-value', 'name' => 'Alice']);
        $this->assertNull($model->secret);
        $this->assertSame('Alice', $model->name);
    }

    #[Test("fromRow handles missing columns gracefully")]
    public function from_row_missing_columns(): void
    {
        $model = ModelStub::fromRow(['id' => 1]);
        $this->assertSame(1, $model->id);
        $this->assertNull($model->name);
    }

    #[Test("toArray returns public column values")]
    public function to_array_returns_public_values(): void
    {
        $model = ModelStub::fromRow(['id' => 1, 'name' => 'Alice', 'age' => 30]);
        $array = $model->toArray();
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('age', $array);
        $this->assertSame('Alice', $array['name']);
    }

    #[Test("toArray converts DateTimeImmutable to string")]
    public function to_array_datetime_conversion(): void
    {
        $model = ModelWithDates::fromRow(['id' => 1, 'created_at' => '2024-01-15 10:30:00']);
        $array = $model->toArray();
        $this->assertSame('2024-01-15 10:30:00', $array['created_at']);
    }

    #[Test("toArray respects protected fields")]
    public function to_array_protected_fields(): void
    {
        $model = ModelWithProtectedFields::fromRow(['id' => 1, 'email' => 'test@example.com', 'name' => 'Alice']);
        $array = $model->toArray();
        $this->assertArrayNotHasKey('email', $array);
        $this->assertArrayHasKey('name', $array);
    }

    #[Test("table returns name from attribute")]
    public function table_name(): void
    {
        $this->assertSame('model_stubs', ModelStub::table());
    }

    #[Test("primaryProperty returns the primary key property")]
    public function primary_property(): void
    {
        $pk = ModelStub::primaryProperty();
        $this->assertSame('id', $pk->getName());
    }

    #[Test("dirty detects changes after fromRow")]
    public function dirty_detects_changes(): void
    {
        $model = ModelStub::fromRow(['id' => 1, 'name' => 'Alice', 'age' => 30]);
        $dirtyMethod = new \ReflectionMethod($model, 'dirty');
        $dirtyMethod->setAccessible(true);

        $before = $dirtyMethod->invoke($model);
        $this->assertSame([], $before);

        $model->name = 'Bob';
        $after = $dirtyMethod->invoke($model);
        $this->assertSame(['name' => 'Bob'], $after);
    }

    #[Test("dirty detects DateTimeImmutable changes by value")]
    public function dirty_datetime_comparison(): void
    {
        $model = ModelWithDates::fromRow(['id' => 1, 'created_at' => '2024-01-15 10:30:00']);
        $dirtyMethod = new \ReflectionMethod($model, 'dirty');
        $dirtyMethod->setAccessible(true);

        $dirty = $dirtyMethod->invoke($model);
        $this->assertSame([], $dirty);
    }

    #[Test("jsonSerialize returns toArray result")]
    public function json_serialize(): void
    {
        $model = ModelStub::fromRow(['id' => 1, 'name' => 'Alice', 'age' => 30]);
        $this->assertSame($model->toArray(), $model->jsonSerialize());
    }

    #[Test("softDeleteColumn returns null for models without trait")]
    public function soft_delete_column_null(): void
    {
        $this->assertNull(ModelStub::softDeleteColumn());
    }

    #[Test("softDeleteColumn returns column name for models with trait")]
    public function soft_delete_column_name(): void
    {
        $this->assertSame('deleted_at', ModelWithSoftDelete::softDeleteColumn());
    }

    #[Test("softDeleteColumn caches result")]
    public function soft_delete_column_caching(): void
    {
        $ref = new \ReflectionProperty(Model::class, 'softDeleteColumnCache');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        ModelWithSoftDelete::softDeleteColumn();
        $cache = $ref->getValue();
        $this->assertArrayHasKey(ModelWithSoftDelete::class, $cache);
        $this->assertSame('deleted_at', $cache[ModelWithSoftDelete::class]);
    }
}

#[Table(name: 'model_stubs')]
class ModelStub extends Model
{
    #[Column(primary: true)]
    public int $id = 0;

    #[Column]
    public ?string $name = null;

    #[Column(cast: Cast::INT)]
    public ?int $age = null;
}

#[Table(name: 'model_with_dates')]
class ModelWithDates extends Model
{
    #[Column(primary: true)]
    public int $id = 0;

    #[Column(cast: Cast::DATETIME)]
    public ?DateTimeImmutable $created_at = null;
}

#[Table(name: 'model_with_hidden')]
class ModelWithHidden extends Model
{
    #[Column(primary: true)]
    public int $id = 0;

    #[Column]
    #[Hidden]
    public ?string $secret = null;

    #[Column]
    public ?string $name = null;
}

#[Table(name: 'model_protected')]
#[ProtectedFields(fields: ['email'])]
class ModelWithProtectedFields extends Model
{
    #[Column(primary: true)]
    public int $id = 0;

    #[Column]
    public ?string $name = null;

    #[Column]
    public ?string $email = null;
}

#[Table(name: 'model_soft_delete')]
class ModelWithSoftDelete extends Model
{
    use SoftDeletes;

    #[Column(primary: true)]
    public int $id = 0;

    #[Column]
    public ?string $name = null;
}
