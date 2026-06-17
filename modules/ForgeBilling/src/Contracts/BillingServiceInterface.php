<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Contracts;

use App\Modules\ForgeBilling\Dto\BillingPlan;
use App\Modules\ForgeBilling\Dto\BillingSubscription;
use App\Modules\ForgeBilling\Dto\Invoice;
use App\Modules\ForgeBilling\Dto\PaymentMethod;
use App\Modules\ForgeBilling\Enums\SubscriptionStatus;

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
