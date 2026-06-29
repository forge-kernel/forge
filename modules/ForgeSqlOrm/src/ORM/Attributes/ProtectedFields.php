<?php
declare(strict_types=1);

namespace Modules\ForgeSqlOrm\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ProtectedFields
{
    public function __construct(
        public array $fields
    )
    {
    }
}

