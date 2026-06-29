<?php

declare(strict_types=1);

namespace Modules\ForgeSaas\Dto;

use Modules\ForgeSaas\Enums\SubscriptionStatus;

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
