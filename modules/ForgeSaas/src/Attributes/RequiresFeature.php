<?php

declare(strict_types=1);

namespace App\Modules\ForgeSaas\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class RequiresFeature
{
    public function __construct(public readonly string $feature) {}
}
