<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Status
{
    public function __construct(
        public string  $column = 'status',
        public array   $values = [],
        public bool    $nullable = true,
        public string  $default = '',
        public ?string $enum = null
    )
    {
        if ($this->enum && empty($this->values)) {
            $this->values = array_column($this->enum::cases(), 'value');
        }
    }
}
