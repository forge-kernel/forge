<?php
declare(strict_types=1);

namespace Modules\ForgeComponents\Definitions;

use Modules\ForgeComponents\Enums\ButtonSize;
use Modules\ForgeComponents\Enums\ButtonVariant;

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
