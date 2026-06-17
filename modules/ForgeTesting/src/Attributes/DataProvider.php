<?php

declare(strict_types=1);

namespace App\Modules\ForgeTesting\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class DataProvider
{
    public function __construct(
        public string $methodName,
    ) {
    }
}
