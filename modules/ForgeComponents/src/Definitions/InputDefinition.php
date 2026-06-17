<?php
declare(strict_types=1);

namespace App\Modules\ForgeComponents\Definitions;

use App\Modules\ForgeComponents\Enums\InputType;

final readonly class InputDefinition
{
    public function __construct(
        public InputType $type = InputType::TEXT,
        public string $name = '',
        public string $id = '',
        public string $label = '',
        public string $placeholder = '',
        public bool $required = false,
        public string $value = '',
        public string $error = '',
    ) {
    }
}
