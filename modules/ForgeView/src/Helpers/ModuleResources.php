<?php

declare(strict_types=1);

namespace Modules\ForgeView\Helpers;

final class ModuleResources
{
    public static function pathTo(string $module, string $resource = 'css'): string
    {
        return "/assets/modules/$module/$resource";
    }
}
