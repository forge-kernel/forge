<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Middlewares;

use Modules\ForgeAuth\Services\RoleService;
use Modules\ForgeAuth\Traits\HasCurrentUser;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;

final class PermissionMiddleware
{
    use HasCurrentUser;

    public function __construct(private readonly RoleService $roleService)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $this->getCurrentUser();

        if (!$user) {
            return new Response("Unauthorized", 401);
        }

        $requiredPermissions =
            $request->getAttribute("required_permissions") ?? [];
        if (empty($requiredPermissions)) {
            return $next($request);
        }

        foreach ($requiredPermissions as $permission) {
            if ($this->roleService->userHasPermission($user, $permission)) {
                return $next($request);
            }
        }

        return new Response("Forbidden - Insufficient permissions", 403);
    }
}
