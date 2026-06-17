<?php

declare(strict_types=1);

namespace App\Modules\FogeNexus\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class CmsModule
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $version = "1.0.0",
        public ?string $author = null,
        public ?string $url = null,
        public string $compatibility = '1.0.0',
        public ?string $license = null
    ) {
    }
}
