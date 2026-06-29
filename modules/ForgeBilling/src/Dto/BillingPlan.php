<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Dto;

final readonly class BillingPlan
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public float $amount,
        public string $currency,
        public string $interval,
        public array $features = [],
        public bool $isActive = true,
        public ?\DateTimeImmutable $createdAt = null,
    ) {
    }
}
