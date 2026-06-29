<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Contracts;

use Modules\ForgeBilling\Dto\BillingPlan;
use Modules\ForgeBilling\Dto\BillingSubscription;
use Modules\ForgeBilling\Dto\Invoice;
use Modules\ForgeBilling\Dto\PaymentMethod;
use Modules\ForgeBilling\Enums\SubscriptionStatus;

interface BillingServiceInterface
{
    public function forTenant(string $tenantId): static;

    // Plans
    public function getPlan(string $id): ?BillingPlan;
    public function getAllPlans(): array;

    // Subscriptions
    public function currentSubscription(): ?BillingSubscription;
    public function isActive(): bool;
    public function onTrial(): bool;

    // Invoices
    public function invoices(): array;
    public function latestInvoice(): ?Invoice;

    // Payment methods
    public function paymentMethods(): array;
    public function defaultPaymentMethod(): ?PaymentMethod;
}
