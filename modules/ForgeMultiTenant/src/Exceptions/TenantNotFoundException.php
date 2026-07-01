<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Exceptions;

use RuntimeException;

final class TenantNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Tenant [{$id}] not found.");
    }
}
