<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Dto;

final readonly class ChargeRequest
{
    public function __construct(
        public string $tenantId,
        public float $amount,
        public string $currency,
        public string $description,
        public array $metadata = [],
    ) {
    }
}
