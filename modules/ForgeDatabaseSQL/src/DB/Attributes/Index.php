<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Index
{
    public function __construct(
        public array  $columns,
        public string $name,
        public bool   $unique = false
    )
    {
    }
}
