<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Middlewares;

use App\Modules\ForgeAuth\Contracts\UserContextInterface;
use App\Modules\ForgeAuth\Services\PermissionService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;

#[Service]
final class HubPermissionMiddleware extends Middleware
{
    public function __construct(
        private readonly UserContextInterface $userContext,
        private readonly ?PermissionService $permissionService = null
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $requiredPermissions = $request->getAttribute('required_permissions', []);

        if (empty($requiredPermissions)) {
            return $next($request);
        }
        if ($this->permissionService === null) {
            Flash::set('error', 'Permission system is not available');
            return Redirect::to('/hub');
        }

        $user = $this->userContext->current();

        if ($user === null) {
            Flash::set('error', 'Authentication required');
            return Redirect::to('/auth/login');
        }

        if (!$this->permissionService->hasAnyPermission($user, $requiredPermissions)) {
            Flash::set('error', 'You do not have permission to access this resource');
            return Redirect::to('/');
        }

        return $next($request);
    }
}
