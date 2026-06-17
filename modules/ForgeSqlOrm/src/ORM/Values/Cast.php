<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM\Values;

use BackedEnum;
use DateMalformedStringException;
use DateTimeImmutable;
use Forge\Core\Dto\BaseDto;
use JsonException;
use ReflectionException;

enum Cast: string
{
    case INT = "int";
    case FLOAT = "float";
    case BOOL = "bool";
    case STRING = "string";
    case JSON = "json";
    case DATE = "date";
    case DATETIME = "datetime";
    case TIMESTAMP = "timestamp";
    case ENUM = "enum";
}

/**
 * @template T
 * @param T $value
 * @param Cast $type
 * @param string|null $dtoClass
 * @return mixed
 * @throws DateMalformedStringException
 * @throws JsonException
 * @throws ReflectionException
 */
function cast(mixed $value, Cast $type, ?string $dtoClass = null): mixed
{
    if ($value === null) {
        return null;
    }

    return match (($trueType = $type)) {
        Cast::INT => (int) $value,
        Cast::FLOAT => (float) $value,
        Cast::BOOL => filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE,
        ) ?? false,
        Cast::STRING => (string) $value,
        Cast::JSON => match (true) {
            is_string($value) => $dtoClass && is_subclass_of($dtoClass, BaseDto::class)
                ? $dtoClass::from(
                    json_decode($value, true, 512, JSON_THROW_ON_ERROR),
                )
                : json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            is_array($value) && $dtoClass && is_subclass_of($dtoClass, BaseDto::class) => $dtoClass::from($value),
            is_array($value) => $value,
            default => $value,
        },
        Cast::ENUM => $dtoClass && is_subclass_of($dtoClass, BackedEnum::class)
            ? $dtoClass::from($value)
            : $value,
        Cast::DATE => new DateTimeImmutable($value),
        Cast::DATETIME => new DateTimeImmutable($value),
        Cast::TIMESTAMP => new DateTimeImmutable($value),
    };
}
