<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions;

final readonly class FooterDefinition
{
    /** @param NavbarLinkDefinition[] $links */
    public function __construct(
        public string $text = '',
        public array $links = [],
        public string $copyright = '',
    ) {
    }
}
