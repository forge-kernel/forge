<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Enums;

enum Permission : string
{
    case HUB_PERMISSIONS = 'hub-permissions';
    case LOGS_READ = 'logs.read';
    case USER_DELETE = 'user.delete';
    case USER_READ = 'user.read';
    case USER_WRITE = 'user.write';
}
