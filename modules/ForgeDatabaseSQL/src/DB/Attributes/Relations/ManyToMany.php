<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Attributes\Relations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class ManyToMany
{
    public function __construct(
        public string $related,
        public string $joinTable,
        public string $foreignKey,
        public string $relatedKey
    )
    {
    }
}
