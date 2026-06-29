<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions\Admin;

final readonly class BreadcrumbItemDefinition
{
    public function __construct(
        public string $label,
        public string $href = '',
        public bool $active = false,
    ) {
    }
}
