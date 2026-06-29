<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions;

final readonly class NavbarLinkDefinition
{
    public function __construct(
        public string $label,
        public string $href = '#',
        public bool $active = false,
    ) {
    }
}
