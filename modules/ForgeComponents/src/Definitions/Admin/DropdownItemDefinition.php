<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions\Admin;

final readonly class DropdownItemDefinition
{
    public function __construct(
        public string $label = '',
        public string $href = '#',
        public ?IconDefinition $icon = null,
        public string $method = 'GET',
        public bool $divider = false,
    ) {
    }
}
