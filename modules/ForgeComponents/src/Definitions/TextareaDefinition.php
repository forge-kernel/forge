<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions;

final readonly class TextareaDefinition
{
    public function __construct(
        public string $name = '',
        public string $id = '',
        public string $label = '',
        public string $placeholder = '',
        public bool $required = false,
        public string $value = '',
        public string $error = '',
        public int $rows = 4,
    ) {
    }
}
