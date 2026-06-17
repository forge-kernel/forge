<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Events;

use App\Modules\ForgeEvents\Attributes\Event;
use App\Modules\ForgeEvents\Enums\QueuePriority;

#[Event(
    queue: "billing",
    maxRetries: 3,
    delay: "0s",
    priority: QueuePriority::NORMAL,
)]
final readonly class GenerateInvoiceEvent
{
    public function __construct(
        public string $tenantId,
        public string $subscriptionId,
        public string $planId,
        public float $planAmount,
        public string $planCurrency,
        public string $planInterval,
    ) {
    }
}
