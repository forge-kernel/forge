<?php
declare(strict_types=1);

namespace App\Modules\ForgeComponents\Definitions\Admin;

final readonly class NavGroupDefinition
{
    /** @param NavItemDefinition[] $items */
    public function __construct(
        public string $heading = '',
        public array $items = [],
    ) {
    }
}
