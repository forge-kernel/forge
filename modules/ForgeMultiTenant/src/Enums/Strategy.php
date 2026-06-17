<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Enums;

enum Strategy: string
{
    case COLUMN = 'column';
    case VIEW   = 'view';
    case DB     = 'database';
}