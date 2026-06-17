<?php

declare(strict_types=1);

namespace App\Modules\FogeNexus\Attributes;

use Attribute;
use Forge\Core\Module\ForgeIcon;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Menu
{
    public function __construct(
        public string $name,
        public ForgeIcon $icon,
        public string $route,
        public array $roles = [],
        public string $order = 99,
        public ?string $parent = null,
        public bool $disabled = false
    ) {
    }
}
