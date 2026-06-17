<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Enums;

enum PlanInterval: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case WEEKLY = 'weekly';
    case ONE_TIME = 'one_time';
}
