<?php

namespace App\Modules\ForgeDebugBar\Collectors;

interface CollectorInterface
{
    public static function collect(...$args): mixed;
}
