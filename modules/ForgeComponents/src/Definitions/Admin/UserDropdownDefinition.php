<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions\Admin;

final readonly class UserDropdownDefinition
{
    /** @param DropdownItemDefinition[] $items */
    public function __construct(
        public string $name = '',
        public string $email = '',
        public string $avatar = '',
        public array $items = [],
    ) {
    }
}
