<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions\Admin;

final readonly class TableDefinition
{
    /** @param TableColumnDefinition[] $columns */
    /** @param array<int, array<string, mixed>> $rows */
    public function __construct(
        public array $columns = [],
        public array $rows = [],
        public ?string $emptyMessage = null,
    ) {
    }
}
