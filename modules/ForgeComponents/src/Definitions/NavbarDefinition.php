<?php
declare(strict_types=1);

namespace App\Modules\ForgeComponents\Definitions;

final readonly class NavbarDefinition
{
    /** @param NavbarLinkDefinition[] $links */
    public function __construct(
        public string $brand = 'Forge',
        public string $brandHref = '/',
        public array $links = [],
        public ?string $authLinkText = 'Sign in',
        public ?string $authLinkHref = '/auth/login',
        public ?string $registerLinkText = 'Get started',
        public ?string $registerLinkHref = '/auth/register',
        public bool $showAuthButtons = true,
        public ?array $user = null,
    ) {
    }
}
