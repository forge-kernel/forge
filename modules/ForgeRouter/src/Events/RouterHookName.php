<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Events;

enum RouterHookName: string
{
    case BEFORE_REQUEST = 'beforeRequest';
    case AFTER_REQUEST = 'afterRequest';
    case AFTER_RESPONSE = 'afterResponse';
}
