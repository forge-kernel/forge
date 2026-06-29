<?php
declare(strict_types=1);

namespace Modules\ForgeSqlOrm\ORM\Attributes;

use Modules\ForgeSqlOrm\ORM\Values\Cast;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Column
{
    public function __construct(
        public bool  $primary = false,
        public ?Cast $cast = null,
        public bool  $hidden = false,
    )
    {
    }
}