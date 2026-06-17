<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Seeders\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class SeederInfo
{
    public function __construct(
        public ?string $description = null,
        public ?string $author = null
    )
    {
    }
}