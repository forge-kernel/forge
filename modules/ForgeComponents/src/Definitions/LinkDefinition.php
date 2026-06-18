<?php
declare(strict_types=1);

namespace App\Modules\ForgeComponents\Definitions;

final readonly class LinkDefinition
{
    public function __construct(
        public string $href = '#',
        public string $text = '',
        public string $variant = 'default',
        public string $size = 'sm',
    ) {
    }
}
