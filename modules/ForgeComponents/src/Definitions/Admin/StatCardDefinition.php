<?php
declare(strict_types=1);

namespace App\Modules\ForgeComponents\Definitions\Admin;

final readonly class StatCardDefinition
{
    public function __construct(
        public string $label = '',
        public string $value = '',
        public ?string $icon = null,
        public string $variant = 'default',
        public ?string $trend = null,
    ) {
    }
}
