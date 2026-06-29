<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Enums;

enum Role: string
{
    case ADMIN = 'ADMIN';
    case USER = 'USER';
}
