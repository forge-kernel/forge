<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Dto;

use Modules\ForgeBilling\Enums\SubscriptionStatus;

final readonly class BillingSubscription
{
    public function __construct(
        public string $id,
        public string $tenantId,
        public BillingPlan $plan,
        public SubscriptionStatus $status,
        public ?\DateTimeImmutable $trialEndsAt = null,
        public ?\DateTimeImmutable $currentPeriodEndsAt = null,
        public ?\DateTimeImmutable $cancelledAt = null,
    ) {
    }
}
