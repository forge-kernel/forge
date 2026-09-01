<?php

declare(strict_types=1);

namespace Modules\ForgeBilling\Events;

use Capability\ForgeEvents\Attributes\Event;
use Capability\ForgeEvents\Enums\QueuePriority;

#[Event(queue: 'billing', maxRetries: 3, delay: '0s', priority: QueuePriority::NORMAL)]
final readonly class GenerateInvoiceEvent
{
    public function __construct(
        public string $tenantId,
        public string $subscriptionId,
        public string $planId,
        public float $planAmount,
        public string $planCurrency,
        public string $planInterval,
    ) {}
}
