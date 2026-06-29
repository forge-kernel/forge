<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions\Admin;

final readonly class BreadcrumbsDefinition
{
    /** @param BreadcrumbItemDefinition[] $items */
    public function __construct(
        public array $items = [],
    ) {
    }
}
