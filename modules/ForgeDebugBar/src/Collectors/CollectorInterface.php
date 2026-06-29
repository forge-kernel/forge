<?php

namespace Modules\ForgeDebugBar\Collectors;

interface CollectorInterface
{
    public static function collect(...$args): mixed;
}
