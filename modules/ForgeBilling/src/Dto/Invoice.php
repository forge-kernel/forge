<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Dto;

use Modules\ForgeBilling\Enums\InvoiceStatus;

final readonly class Invoice
{
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $subscriptionId,
        public string $number,
        public float $amount,
        public string $currency,
        public InvoiceStatus $status,
        public array $items = [],
        public ?\DateTimeImmutable $paidAt = null,
        public ?\DateTimeImmutable $dueDate = null,
        public ?\DateTimeImmutable $createdAt = null,
    ) {
    }
}
