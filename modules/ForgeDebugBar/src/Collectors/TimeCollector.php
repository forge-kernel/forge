<?php

namespace App\Modules\ForgeDebugBar\Collectors;

class TimeCollector implements CollectorInterface
{
    public static function collect(...$args): string
    {
        $startTime = $args[0] ?? microtime(true);
        return round((microtime(true) - $startTime) * 1000, 2) . 'ms';
    }

    public static function getStartTime(): float
    {
        return microtime(true);
    }
}
