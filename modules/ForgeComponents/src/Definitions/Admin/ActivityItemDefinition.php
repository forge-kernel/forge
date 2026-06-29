<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions\Admin;

final readonly class ActivityItemDefinition
{
    public function __construct(
        public string $title = '',
        public string $time = '',
        public ?string $icon = null,
        public string $variant = 'default',
    ) {
    }
}
