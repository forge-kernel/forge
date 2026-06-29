<?php
declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class TenantScoped {}