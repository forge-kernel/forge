<?php
declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class TenantScope
{
    public const CENTRAL = 'central';
    public const TENANT  = 'tenant';

    public function __construct(
        public string $value
    ) {}
}