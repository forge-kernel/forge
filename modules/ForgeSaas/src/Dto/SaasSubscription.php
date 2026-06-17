<?php

declare(strict_types=1);

namespace App\Modules\ForgeSaas\Dto;

use App\Modules\ForgeSaas\Enums\SubscriptionStatus;

final readonly class SaasSubscription
{
    public function __construct(
        public string $id,
        public string $tenantId,
        public SaasPlan $plan,
        public SubscriptionStatus $status,
        public ?\DateTimeImmutable $trialEndsAt,
        public ?\DateTimeImmutable $currentPeriodEndsAt,
    ) {}
}
