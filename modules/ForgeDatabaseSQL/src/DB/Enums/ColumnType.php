<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Enums;

enum ColumnType: string
{
    case UUID = 'UUID';
    case STRING = 'STRING';
    case TEXT = 'TEXT';
    case INTEGER = 'INTEGER';
    case BOOLEAN = 'BOOLEAN';
    case FLOAT = 'FLOAT';
    case DECIMAL = 'DECIMAL';
    case DATE = 'DATE';
    case DATETIME = 'DATETIME';
    case TIMESTAMP = 'TIMESTAMP';
    case ENUM = 'ENUM';
    case JSON = 'JSON';

    public function withLength(int $length): string
    {
        return match ($this) {
            self::STRING => "VARCHAR($length)",
            default => $this->value,
        };
    }

    public function withPrecisionScale(int $precision, int $scale): string
    {
        return match ($this) {
            self::DECIMAL => "DECIMAL($precision, $scale)",
            default => $this->value,
        };
    }

    public function defaultValue(): mixed
    {
        return match ($this) {
            self::INTEGER => 0,
            self::STRING => '',
            self::BOOLEAN => false,
            self::FLOAT => 0.0,
            self::DECIMAL => '0.00',
            self::DATE => null,
            self::DATETIME => null,
            self::TIMESTAMP => 'CURRENT_TIMESTAMP',
            default => null,
        };
    }
}
