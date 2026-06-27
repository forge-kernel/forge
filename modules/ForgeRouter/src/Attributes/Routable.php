<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Routable
{
    public function __construct(
        public string $prefix = '',
    ) {}
}
