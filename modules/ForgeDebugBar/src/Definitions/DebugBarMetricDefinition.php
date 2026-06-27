<?php

declare(strict_types=1);

namespace App\Modules\ForgeDebugBar\Definitions;

final readonly class DebugBarMetricDefinition
{
    public function __construct(
        public string $label,
        public string $value,
        public ?string $state = null,
    ) {
    }
}
