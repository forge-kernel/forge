<?php
declare(strict_types=1);

namespace Modules\ForgeSqlOrm\Dto;

use Modules\ForgeSqlOrm\Dto\Attributes\Sanitize;
use ReflectionClass;
use ReflectionException;

/**
 * @template T of static
 */
abstract class BaseDto
{
    public static function fromList(array $rows): array
    {
        return array_map(static::from(...), $rows);
    }

    /**
     * @throws ReflectionException
     */
    public static function from(array $data): static
    {
        $r = new ReflectionClass(static::class);
        $ctor = $r->getConstructor();

        if (!$ctor) {
            $obj = $r->newInstanceWithoutConstructor();
            foreach ($data as $key => $value) {
                if ($r->hasProperty($key)) {
                    $prop = $r->getProperty($key);
                    $prop->setAccessible(true);
                    $prop->setValue($obj, $value);
                }
            }
            return $obj;
        }

        $params = $ctor->getParameters();
        if (
            count($params) === 1 &&
            $params[0]->getType()?->getName() === "array"
        ) {
            return $r->newInstance([$data]);
        }

        $args = [];
        foreach ($params as $p) {
            $name = $p->getName();
            $value = array_key_exists($name, $data)
                ? $data[$name]
                : ($p->isDefaultValueAvailable()
                    ? $p->getDefaultValue()
                    : null);

            $type = $p->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();
                if (
                    class_exists($typeName) &&
                    is_subclass_of($typeName, self::class) &&
                    is_array($value)
                ) {
                    $value = $typeName::from($value);
                }
            }

            if (
                $type instanceof \ReflectionNamedType &&
                $type->getName() === "bool"
            ) {
                $value =
                    filter_var(
                        $value,
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE,
                    ) ?? false;
            }

            $args[] = $value;
        }

        return $r->newInstanceArgs($args);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @throws ReflectionException
     */
    public function sanitize(): static
    {
        $r = new ReflectionClass($this);
        $props = $r->getProperties();
        $cleanData = [];

        $sanAttr = $r->getAttributes(Sanitize::class)[0] ?? null;
        $toWipe = $sanAttr ? $sanAttr->newInstance()->properties : [];

        foreach ($props as $p) {
            $name = $p->getName();

            if (!$p->isPublic()) {
                $p->setAccessible(true);
            }

            $cleanData[$name] = in_array($name, $toWipe, true)
                ? null
                : $p->getValue($this);
        }

        $newObj = $r->newInstanceWithoutConstructor();

        foreach ($cleanData as $key => $value) {
            if ($r->hasProperty($key)) {
                $prop = $r->getProperty($key);
                $prop->setAccessible(true);
                $prop->setValue($newObj, $value);
            }
        }

        return $newObj;
    }
}
