<?php
declare(strict_types=1);

namespace App\Modules\ForgeComponents\Definitions\Admin;

final readonly class NavItemDefinition
{
    public function __construct(
        public string $label,
        public string $href = '#',
        public ?IconDefinition $icon = null,
        public bool $active = false,
        public ?string $badge = null,
        /** @var NavItemDefinition[] */
        public array $children = [],
    ) {
    }
}
