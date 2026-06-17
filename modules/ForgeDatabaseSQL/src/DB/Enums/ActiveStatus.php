<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Enums;

use Forge\Traits\EnumHelper;

enum ActiveStatus: string
{
    use EnumHelper;

    case ACTIVE = 'ACTIVE';
    case COMPLETED = 'DELETED';
    case PENDING_DELETION = 'PENDING_DELETION';
    case ARCHIVED = 'ARCHIVED';
}
