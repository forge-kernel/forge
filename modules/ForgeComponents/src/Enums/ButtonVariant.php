<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Enums;

enum ButtonVariant: string
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case DANGER = 'danger';
    case GHOST = 'ghost';
}
