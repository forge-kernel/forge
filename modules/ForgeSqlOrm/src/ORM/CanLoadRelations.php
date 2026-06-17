<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM;

use App\Modules\ForgeSqlOrm\ORM\Values\Relate;
use App\Modules\ForgeSqlOrm\ORM\Values\Relation;
use App\Modules\ForgeSqlOrm\ORM\Values\RelationKind;
use LogicException;
use ReflectionException;
use ReflectionMethod;

trait CanLoadRelations
{
    private array $relations = [];

    public static function with(string ...$paths): ModelQuery
    {
        return static::query()->with(...$paths);
    }

    /**
     * @throws ReflectionException
     */
    public function load(string ...$relations): self
    {
        (new RelationLoader($this))->load(...$relations);
        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function relation(string $name): ModelQuery
    {
        $rel = $this->describe($name);
        return match ($rel->kind) {
            RelationKind::BelongsTo => $this->belongsToQuery($rel),
            RelationKind::HasOne, RelationKind::HasMany => $this->hasQuery($rel),
        };
    }

    /**
     * @throws ReflectionException
     */
    public static function describe(string $method): Relation
    {
        $reflect = new ReflectionMethod(static::class, $method);
        $attr = $reflect->getAttributes(Relate::class)[0] ?? null;
        if ($attr === null) {
            throw new LogicException("Method {$method} lacks #[Relate] attribute");
        }
        /** @var Relate $r */
        $r = $attr->newInstance();
        return new Relation($r->kind, $r->target, $r->foreignKey, $r->localKey);
    }

    private function belongsToQuery(Relation $rel): ModelQuery
    {
        /** @var Model $target */
        $target = $rel->target;
        $localValue = $this->{$rel->localKey};
        
        // Handle null local key by returning an impossible query that returns no results
        if ($localValue === null) {
            return $target::query()->where('1', '=', '0');
        }
        
        return $target::query()->where($rel->foreignKey, '=', $localValue);
    }

    private function hasQuery(Relation $rel): ModelQuery
    {
        /** @var Model $target */
        $target = $rel->target;
        $localValue = $this->{$rel->localKey};
        
        // Handle null local key by returning an impossible query that returns no results
        if ($localValue === null) {
            return $target::query()->where('1', '=', '0');
        }
        
        return $target::query()->where($rel->foreignKey, '=', $localValue);
    }
}