<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Enums;

enum InvoiceStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELED = 'canceled';
    case REFUNDED = 'refunded';
}
