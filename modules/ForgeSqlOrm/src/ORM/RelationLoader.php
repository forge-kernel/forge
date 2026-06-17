<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM;

use App\Modules\ForgeSqlOrm\ORM\Values\RelationKind;
use ReflectionException;

final class RelationLoader
{
    /** @var Model[] */
    private array $parents;

    public function __construct(Model ...$parents)
    {
        $this->parents = $parents;
    }

    /**
     * @throws ReflectionException
     */
    public function load(string ...$relations): void
    {
        $relationMap = $this->groupRelations($relations);
        
        foreach ($relationMap as $relation => $nested) {
            $this->loadOne($relation, $nested);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function loadOne(string $relation, array $nested = []): void
    {
        if (empty($this->parents)) {
            return;
        }

        $rel = $this->parents[0]::describe($relation);
        $kind = $rel->kind;

        $localKeysMap = [];
        $parentIndexMap = [];
        
        foreach ($this->parents as $index => $p) {
            $key = $p->{$rel->localKey};
            // Handle null keys properly
            if ($key === null || $key === '' || $key === '0') {
                continue;
            }
            $stringKey = (string)$key;
            $localKeysMap[$stringKey] = true;
            if (!isset($parentIndexMap[$stringKey])) {
                $parentIndexMap[$stringKey] = [];
            }
            $parentIndexMap[$stringKey][] = $index;
        }

        $localKeys = array_keys($localKeysMap);
        if ($localKeys === []) {
            foreach ($this->parents as $parent) {
                $parent->setRelation($relation, $kind === RelationKind::HasOne ? null : []);
            }
            return;
        }

        /** @var Model $target */
        $target = $rel->target;
        $foreignColumn = $rel->foreignKey;

        $children = $target::query()
            ->whereIn($foreignColumn, $localKeys)
            ->get();

        $bucket = [];
        foreach ($children as $child) {
            $foreignKey = (string)$child->{$foreignColumn};
            if ($kind === RelationKind::HasOne) {
                $bucket[$foreignKey] = $child;
            } else {
                if (!isset($bucket[$foreignKey])) {
                    $bucket[$foreignKey] = [];
                }
                $bucket[$foreignKey][] = $child;
            }
        }

        foreach ($this->parents as $index => $parent) {
            $key = $parent->{$rel->localKey};
            // Handle null keys properly
            if ($key === null || $key === '' || $key === '0') {
                $parent->setRelation($relation, $kind === RelationKind::HasOne ? null : []);
                continue;
            }
            $stringKey = (string)$key;
            $value = $bucket[$stringKey] ?? ($kind === RelationKind::HasOne ? null : []);
            $parent->setRelation($relation, $value);
            
            if (!empty($nested) && $value !== null) {
                $childrenToLoad = $kind === RelationKind::HasOne ? [$value] : $value;
                if (!empty($childrenToLoad)) {
                    $loader = new RelationLoader(...$childrenToLoad);
                    $loader->load(...$nested);
                }
            }
        }
    }

    private function groupRelations(array $relations): array
    {
        $grouped = [];
        
        foreach ($relations as $relation) {
            $parts = explode('.', $relation, 2);
            $main = $parts[0];
            $nested = isset($parts[1]) ? [$parts[1]] : [];
            
            if (!isset($grouped[$main])) {
                $grouped[$main] = [];
            }
            
            if (!empty($nested)) {
                $grouped[$main] = array_merge($grouped[$main], $nested);
            }
        }
        
        return $grouped;
    }
}