<?php

declare(strict_types=1);

namespace Modules\ForgeMultiTenant\Exceptions;

use RuntimeException;

final class TenantNotFoundException extends RuntimeException
{
    public function __construct(string $id)
    {
        $message = $id === 'no-context'
            ? 'No tenant context available. This route requires a tenant to be resolved.'
            : "Tenant [{$id}] not found.";

        parent::__construct($message);
    }
}
