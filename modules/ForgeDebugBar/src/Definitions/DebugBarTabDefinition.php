<?php

declare(strict_types=1);

namespace Modules\ForgeDebugBar\Definitions;

final readonly class DebugBarTabDefinition
{
    public function __construct(
        public string $name,
        public string $label,
        public string $component,
        public ?int $count = null,
        public array $data = [],
    ) {
    }
}
