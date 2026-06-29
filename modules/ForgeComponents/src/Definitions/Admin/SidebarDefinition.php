<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions\Admin;

final readonly class SidebarDefinition
{
    /** @param NavGroupDefinition[] $groups */
    public function __construct(
        public string $brand = '',
        public string $brandHref = '/',
        public string $tagline = '',
        public string $logoUrl = '',
        public array $groups = [],
        /** @var NavItemDefinition[] */
        public array $footerLinks = [],
        public bool $statusOnline = false,
        public string $statusLabel = '',
        public string $contextLabel = '',
        public string $contextSubLabel = '',
    ) {
    }
}
