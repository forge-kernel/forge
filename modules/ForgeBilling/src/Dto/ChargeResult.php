<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Dto;

final readonly class ChargeResult
{
    public function __construct(
        public bool $success,
        public string $transactionId,
        public float $amountCharged,
        public string $currency,
        public array $providerResponse = [],
        public ?string $errorMessage = null,
    ) {
    }
}
