<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Traits;

use App\Modules\ForgeRouter\Http\Request;
use Forge\Exceptions\AuthorizationException;

trait AuthorizeRequests
{
    protected function authorize(Request $request, array $required): void
    {
        $userPermissions = $request->getAttribute('api_key_permissions') ?? [];

        if (count(array_diff($required, $userPermissions)) > 0) {
            throw new AuthorizationException();
        }
    }
}
