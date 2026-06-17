<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class SoftDelete
{
    public function __construct(
        public string $column = 'deleted_at'
    )
    {
    }
}
