<?php

declare(strict_types=1);

namespace Modules\ForgeEvents\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class EventListener
{
    public function __construct(
        public string $eventClass
    ) {
    }
}
