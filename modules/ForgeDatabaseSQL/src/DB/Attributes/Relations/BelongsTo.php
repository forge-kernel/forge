<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Attributes\Relations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class BelongsTo
{
    public function __construct(
        public string  $related,
        public ?string $foreignKey = null,
        public string  $onDelete = 'CASCADE'
    )
    {
    }
}
