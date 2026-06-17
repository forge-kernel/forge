<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Middlewares;

use App\Modules\ForgeAuth\Services\RoleService;
use App\Modules\ForgeAuth\Traits\HasCurrentUser;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;

#[Service]
final class RoleMiddleware
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

        $requiredRoles = $request->getAttribute("required_roles") ?? [];
        if (empty($requiredRoles)) {
            return $next($request);
        }

        foreach ($requiredRoles as $role) {
            if ($this->roleService->userHasRole($user, $role)) {
                return $next($request);
            }
        }

        ob_start();
        $errorCode = 403;
        require BASE_PATH . "/kernel/Templates/Views/error_page.php";
        $content = ob_get_clean();

        return new Response($content, (int) $errorCode);
    }
}
