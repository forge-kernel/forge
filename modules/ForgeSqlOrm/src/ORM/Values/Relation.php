<?php

declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM\Values;

final readonly class Relation
{
    public function __construct(
        public RelationKind $kind,
        public string       $target,
        public string       $foreignKey,
        public string       $localKey,
    )
    {
    }
}