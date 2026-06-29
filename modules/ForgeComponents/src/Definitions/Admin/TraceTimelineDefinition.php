<?php

declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions\Admin;

final readonly class TraceTimelineDefinition
{
    public function __construct(
        public array $spans = [],
        public float $totalDuration = 0.0,
        public bool $showLabels = true,
    ) {
    }
}
