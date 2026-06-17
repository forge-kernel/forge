<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Events;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class RouterHookAttribute
{
    public function __construct(public RouterHookName $hook) {}
}
