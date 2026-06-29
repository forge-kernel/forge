<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final class Layout
{
    public function __construct(public readonly string $name) {}
}
