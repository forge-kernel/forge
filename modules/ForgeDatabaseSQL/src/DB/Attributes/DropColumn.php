<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Attributes;

use Attribute;

/**
 * Attribute to drop a column from an existing table.
 * 
 * Example:
 * #[DropColumn(table: 'users', name: 'temporary_field')]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class DropColumn
{
    public function __construct(
        public string $table,
        public string $name,
    )
    {
    }
}
