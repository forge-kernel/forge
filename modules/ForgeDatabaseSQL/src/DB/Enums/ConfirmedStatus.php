<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Enums;

use Forge\Traits\EnumHelper;

enum ConfirmedStatus: string
{
    use EnumHelper;

    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case REJECTED = 'REJECTED';
    case EXPIRED = 'EXPIRED';
}
