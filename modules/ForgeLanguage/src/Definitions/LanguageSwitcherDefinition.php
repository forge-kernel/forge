<?php

declare(strict_types=1);

namespace Modules\ForgeLanguage\Definitions;

final readonly class LanguageSwitcherDefinition
{
    public function __construct(
        public bool $showFlags = true,
        public bool $showLabels = true,
        public bool $showCodes = false,
        public bool $showCurrent = true,
        public string $wrapperClass = '',
        public string $itemClass = '',
        public string $activeClass = 'active',
    ) {
    }
}