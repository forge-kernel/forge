<?php
declare(strict_types=1);

namespace App\Modules\AppAuth\Definitions;

final readonly class AuthCardDefinition
{
    public function __construct(
        public string $heading,
        public string $subtitle = '',
        public string $form = '',
        public array $footerLink = [],
        public string $footerText = '',
    ) {
    }
}
