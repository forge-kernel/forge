<?php

declare(strict_types=1);

use App\Modules\ForgeBilling\Services\BillingSubscriptionService;
use Forge\Core\DI\Container;

if (!function_exists('billing_subscription')) {
    function billing_subscription(): ?\App\Modules\ForgeBilling\Dto\BillingSubscription
    {
        return Container::getInstance()->get(BillingSubscriptionService::class)->current();
    }
}

if (!function_exists('billing_is_active')) {
    function billing_is_active(): bool
    {
        return Container::getInstance()->get(BillingSubscriptionService::class)->isActive();
    }
}

if (!function_exists('billing_on_trial')) {
    function billing_on_trial(): bool
    {
        return Container::getInstance()->get(BillingSubscriptionService::class)->onTrial();
    }
}
