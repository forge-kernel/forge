<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Attributes;

use Attribute;

/**
 * Attribute to rename a column in an existing table.
 * 
 * Example:
 * #[RenameColumn(table: 'users', old: 'username', new: 'user_name')]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class RenameColumn
{
    public function __construct(
        public string $table,
        public string $old,
        public string $new,
    )
    {
    }
}
