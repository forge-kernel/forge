<?php
declare(strict_types=1);

namespace App\Modules\ForgeRouter\Middleware\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Middleware
{
    public const string DEFAULT_GROUP = 'global';

    public function __construct(
        public string $group = self::DEFAULT_GROUP,
        public int $order = 500,
        public bool $allowDuplicate = false,
        public ?string $overrideClass = null,
        public bool $enabled = true,
    ) {
    }
}
