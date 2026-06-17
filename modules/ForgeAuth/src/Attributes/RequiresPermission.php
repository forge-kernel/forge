<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class RequiresPermission
{
    public array $permissions;

    public function __construct(string|array $permissions)
    {
        $this->permissions = is_array($permissions) ? $permissions : [$permissions];
    }
}