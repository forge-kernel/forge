<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RequiresRole
{
    public function __construct(public string $role) {}
}
