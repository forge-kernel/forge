<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Attributes;

use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Column
{
    private const NOT_SET = '__COLUMN_DEFAULT_NOT_SET__';

    public function __construct(
        public string            $name,
        public string|ColumnType $type,
        public bool              $nullable = false,
        public mixed             $default = self::NOT_SET,
        public bool              $unique = false,
        public bool              $primaryKey = false,
        public bool              $autoIncrement = false,
        public ?array            $enum = null,
        public ?int              $length = null,
        public ?int              $precision = null,
        public ?int              $scale = null,
        public bool              $unsigned = false,
        public ?string           $comment = null,
        public ?string           $check = null,
    )
    {
        if ($this->type === 'UUID' && $this->autoIncrement || $this->type === ColumnType::UUID && $this->autoIncrement) {
            throw new InvalidArgumentException("UUID columns cannont be auto-incremented");
        }
        if ($this->default === self::NOT_SET && $this->type instanceof ColumnType) {
            $this->default = $this->type->defaultValue();
        }
        if ($this->default === self::NOT_SET) {
            $this->default = null;
        }
    }
}
