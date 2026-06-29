<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\DB\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class GroupMigration
{
    public function __construct(
        public string $name,
    )
    {
    }
}
