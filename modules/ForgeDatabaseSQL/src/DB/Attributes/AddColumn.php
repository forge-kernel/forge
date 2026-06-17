<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Attributes;

use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use Attribute;

/**
 * Attribute to add a column to an existing table.
 * 
 * Example:
 * #[AddColumn(table: 'users', name: 'phone', type: ColumnType::STRING, length: 20)]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class AddColumn
{
    public function __construct(
        public string            $table,
        public string            $name,
        public string|ColumnType $type,
        public bool              $nullable = false,
        public mixed             $default = null,
        public ?int              $length = null,
        public ?int              $precision = null,
        public ?int              $scale = null,
        public bool              $unsigned = false,
        public ?string           $comment = null,
        public ?string           $after = null,
        public bool              $first = false,
    )
    {
        if ($this->default === null && $this->type instanceof ColumnType) {
            $this->default = $this->type->defaultValue();
        }
    }
}
