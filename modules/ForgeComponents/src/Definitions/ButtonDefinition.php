<?php
declare(strict_types=1);

namespace App\Modules\ForgeComponents\Definitions;

use App\Modules\ForgeComponents\Enums\ButtonSize;
use App\Modules\ForgeComponents\Enums\ButtonVariant;

final readonly class ButtonDefinition
{
    public function __construct(
        public string $type = 'submit',
        public ButtonVariant $variant = ButtonVariant::PRIMARY,
        public ButtonSize $size = ButtonSize::MD,
        public bool $block = false,
    ) {
    }
}
