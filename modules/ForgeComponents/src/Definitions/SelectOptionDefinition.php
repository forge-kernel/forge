<?php
declare(strict_types=1);

namespace App\Modules\ForgeComponents\Definitions;

final readonly class SelectOptionDefinition
{
    public function __construct(
        public string $value,
        public string $label,
        public bool $selected = false,
    ) {
    }
}
