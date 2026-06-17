<?php

use App\Modules\ForgeSaas\Contracts\SubscriptionManagerInterface;
use App\Modules\ForgeSaas\Dto\SaasPlan;
use Forge\Core\DI\Container;

if (!function_exists('saas_manager')) {
    function saas_manager(): ?SubscriptionManagerInterface
    {
        try {
            return Container::getInstance()->get(SubscriptionManagerInterface::class);
        } catch (\Throwable) {
            return null;
        }
    }
}

if (!function_exists('tenant_can')) {
    function tenant_can(string $feature): bool
    {
        return saas_manager()?->hasFeature($feature) ?? false;
    }
}

if (!function_exists('tenant_limit')) {
    function tenant_limit(string $resource): int
    {
        return saas_manager()?->limitFor($resource) ?? PHP_INT_MAX;
    }
}

if (!function_exists('tenant_within_limit')) {
    function tenant_within_limit(string $resource, int $currentCount): bool
    {
        $manager = saas_manager();
        if ($manager === null) {
            return true;
        }
        return $manager->withinLimit($resource, $currentCount);
    }
}

if (!function_exists('tenant_on_plan')) {
    function tenant_on_plan(string $planSlug): bool
    {
        return saas_manager()?->onPlan($planSlug) ?? false;
    }
}

if (!function_exists('tenant_subscription_active')) {
    function tenant_subscription_active(): bool
    {
        return saas_manager()?->isActive() ?? false;
    }
}

if (!function_exists('tenant_plan')) {
    function tenant_plan(): ?SaasPlan
    {
        return saas_manager()?->currentPlan();
    }
}
