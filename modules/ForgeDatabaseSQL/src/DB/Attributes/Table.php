<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Table
{
    public function __construct(
        public string $name,
        public bool   $ifNotExists = false
    )
    {
    }
}
