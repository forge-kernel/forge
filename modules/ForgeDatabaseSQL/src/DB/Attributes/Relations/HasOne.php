<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\DB\Attributes\Relations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class HasOne
{
    public function __construct(
        public string  $related,
        public ?string $foreignKey = null,
        public string  $onDelete = 'CASCADE'
    )
    {
    }
}
