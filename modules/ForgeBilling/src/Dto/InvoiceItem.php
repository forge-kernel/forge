<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Dto;

final readonly class InvoiceItem
{
    public function __construct(
        public string $id,
        public string $invoiceId,
        public string $description,
        public float $amount,
        public string $currency,
        public int $quantity = 1,
    ) {
    }
}
