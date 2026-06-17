<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Attributes;

use App\Modules\ForgeEvents\Enums\QueuePriority;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Event
{
    /**
     * @param string $queue Queue name
     * @param int $maxRetries Max number of retries
     * @param int $retryDelay Delay between retries in ms
     * @param string $delay Delay before first processing, e.g. '10m', '30s', '2h'
     * @param QueuePriority $priority Event priority
     */
    public function __construct(
        public string $queue = 'default',
        public int $maxRetries = 1,
        public int $retryDelay = 1000,
        public string $delay = '0s',
        public QueuePriority $priority = QueuePriority::NORMAL
    ) {
    }
}
