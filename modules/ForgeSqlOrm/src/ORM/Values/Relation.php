<?php

declare(strict_types=1);

namespace Modules\ForgeSqlOrm\ORM\Values;

final readonly class Relation
{
    public function __construct(
        public RelationKind $kind,
        public string       $target,
        public string       $foreignKey,
        public string       $localKey,
        public ?string      $pivotTable = null,
        public ?string      $pivotForeignKey = null,
        public ?string      $pivotLocalKey = null,
    )
    {
    }
}