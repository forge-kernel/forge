<?php

declare(strict_types=1);

namespace App\Modules\ForgeSaas\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class RequiresPlan
{
    public function __construct(public readonly string $plan) {}
}
