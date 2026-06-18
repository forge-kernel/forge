<?php
declare(strict_types=1);

namespace App\Modules\ForgeComponents\Definitions;

final readonly class SelectDefinition
{
    /** @param SelectOptionDefinition[] $options */
    public function __construct(
        public string $name = '',
        public string $id = '',
        public string $label = '',
        public array $options = [],
        public bool $required = false,
        public string $error = '',
        public string $placeholder = '',
    ) {
    }
}
