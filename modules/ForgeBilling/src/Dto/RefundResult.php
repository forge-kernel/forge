<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Dto;

final readonly class RefundResult
{
    public function __construct(
        public bool $success,
        public string $refundId,
        public float $amountRefunded,
        public string $currency,
        public array $providerResponse = [],
        public ?string $errorMessage = null,
    ) {
    }
}
