<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Enums;

enum PaymentMethodType: string
{
    case CARD = 'card';
    case PAYPAL = 'paypal';
    case MANUAL = 'manual';
    case OTHER = 'other';
}
