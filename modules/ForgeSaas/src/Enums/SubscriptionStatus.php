<?php

declare(strict_types=1);

namespace Modules\ForgeSaas\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE   = 'active';
    case TRIAL    = 'trial';
    case PAST_DUE = 'past_due';
    case CANCELED = 'canceled';
}
