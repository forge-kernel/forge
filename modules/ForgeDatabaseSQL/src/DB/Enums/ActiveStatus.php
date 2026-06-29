<?php

declare(strict_types=1);

namespace Modules\ForgeDatabaseSQL\DB\Enums;

use Forge\Traits\EnumHelper;

enum ActiveStatus: string
{
    use EnumHelper;

    case ACTIVE = 'ACTIVE';
    case COMPLETED = 'COMPLETED';
    case PENDING_DELETION = 'PENDING_DELETION';
    case ARCHIVED = 'ARCHIVED';
}
