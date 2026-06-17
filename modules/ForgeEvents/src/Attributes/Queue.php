<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Attributes;

use App\Modules\ForgeEvents\Enums\QueuePriority;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS|Attribute::IS_REPEATABLE)]
final class Queue
{
    public function __construct(
        public string $name = 'default',
        public int $maxRetries = 3,
        public int $retryDelay = 1000,
        public QueuePriority $priority = QueuePriority::NORMAL
    ) {
    }
}
