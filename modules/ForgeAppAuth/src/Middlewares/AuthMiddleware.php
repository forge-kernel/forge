<?php
declare(strict_types=1);

namespace Modules\ForgeAppAuth\Middlewares;

use Modules\ForgeAppAuth\Services\UserContext;
use Modules\ForgeRouter\Helpers\Redirect;
use Modules\ForgeRouter\Http\Middleware as MiddlewareImpl;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\Middleware;
use Forge\Core\Services\RedirectHandlerService;

#[Middleware(group: 'auth')]
final class AuthMiddleware extends MiddlewareImpl
{
    public function __construct(
        private readonly UserContext $userContext,
        private readonly RedirectHandlerService $redirectHandler,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!$this->userContext->current()) {
            $intendedUrl = $request->serverParams["REQUEST_URI"] ?? "/";
            $this->redirectHandler->setIntendedUrl($intendedUrl);

            return Redirect::to("/auth/login", 401);
        }

        return $next($request);
    }
}
