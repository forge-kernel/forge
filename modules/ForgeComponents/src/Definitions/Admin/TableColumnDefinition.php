<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions\Admin;

final readonly class TableColumnDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public bool $sortable = false,
    ) {
    }
}
