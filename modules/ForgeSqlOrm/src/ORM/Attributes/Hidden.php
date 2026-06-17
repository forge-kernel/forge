<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Hidden
{
}