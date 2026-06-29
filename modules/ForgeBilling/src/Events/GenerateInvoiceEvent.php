<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Events;

use Modules\ForgeEvents\Attributes\Event;
use Modules\ForgeEvents\Enums\QueuePriority;

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
