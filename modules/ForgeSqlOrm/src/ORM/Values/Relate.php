<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM\Values;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Relate
{
    public function __construct(
        public RelationKind $kind,
        public string       $target,
        public string       $foreignKey,
        public string       $localKey = 'id',
    )
    {
    }
}