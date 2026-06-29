<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\DB\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Timestamps
{
    public function __construct(
        public string $createdAt = 'created_at',
        public string $updatedAt = 'updated_at'
    )
    {
    }
}
