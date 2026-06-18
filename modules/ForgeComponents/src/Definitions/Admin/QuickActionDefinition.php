<?php
declare(strict_types=1);

namespace App\Modules\ForgeComponents\Definitions\Admin;

final readonly class QuickActionDefinition
{
    public function __construct(
        public string $label = '',
        public string $href = '#',
        public string $variant = 'primary',
        public ?string $icon = null,
    ) {
    }
}
