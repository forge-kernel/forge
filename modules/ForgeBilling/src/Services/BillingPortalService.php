<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Services;

use Modules\ForgeBilling\Dto\BillingPlan;
use Modules\ForgeBilling\Dto\BillingSubscription;
use Modules\ForgeBilling\Dto\Invoice;
use Modules\ForgeBilling\Dto\PaymentMethod;
use Forge\Core\DI\Attributes\Service;

final class BillingPortalService
{
    public function __construct(
        private readonly BillingPlanService $planService,
        private readonly BillingSubscriptionService $subscriptionService,
        private readonly InvoiceService $invoiceService,
    ) {
    }

    public function overview(string $tenantId): array
    {
        $subscription = $this->subscriptionService->forTenant($tenantId)->current();
        $latestInvoice = $this->invoiceService->latestForTenant($tenantId);
        $invoices = $this->invoiceService->getForTenant($tenantId);
        $plans = $this->planService->getAll();

        return [
            'subscription' => $subscription,
            'latestInvoice' => $latestInvoice,
            'invoices' => $invoices,
            'plans' => $plans,
            'isActive' => $this->subscriptionService->isActive(),
            'onTrial' => $this->subscriptionService->onTrial(),
        ];
    }
}
