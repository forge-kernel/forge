<?php
declare(strict_types=1);

namespace App\Modules\ForgeComponents\Definitions;

final readonly class CheckboxDefinition
{
    public function __construct(
        public string $name = '',
        public string $id = '',
        public string $label = '',
        public bool $checked = false,
        public bool $required = false,
        public string $value = '1',
    ) {
    }
}
