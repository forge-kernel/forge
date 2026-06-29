<?php

declare(strict_types=1);

namespace Modules\ForgeEvents\Enums;

enum QueuePriority: int
{
    case HIGH = 3;
    case NORMAL = 2;
    case LOW = 1;

    public static function default(): self
    {
        return self::NORMAL;
    }
}
