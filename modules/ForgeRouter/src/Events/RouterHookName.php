<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Events;

enum RouterHookName: string
{
    case BEFORE_REQUEST = 'beforeRequest';
    case AFTER_REQUEST = 'afterRequest';
    case AFTER_RESPONSE = 'afterResponse';
}
