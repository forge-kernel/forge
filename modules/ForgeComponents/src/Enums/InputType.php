<?php
declare(strict_types=1);

namespace App\Modules\ForgeComponents\Enums;

enum InputType: string
{
    case TEXT = 'text';
    case EMAIL = 'email';
    case PASSWORD = 'password';
    case NUMBER = 'number';
    case TEL = 'tel';
    case URL = 'url';
    case DATE = 'date';
    case TIME = 'time';
    case FILE = 'file';
    case HIDDEN = 'hidden';
}
