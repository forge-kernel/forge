<?php

declare(strict_types=1);

namespace Modules\ForgeAuth\Middlewares;

use Forge\Core\DI\Container;
use Modules\ForgeAuth\Services\RoleService;
use Modules\ForgeAuth\Traits\HasCurrentUser;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Services\ErrorPageRenderer;

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

        $renderer = Container::getInstance()->make(ErrorPageRenderer::class);
        $content = $renderer->render(403);

        return new Response($content, 403);
    }
}
