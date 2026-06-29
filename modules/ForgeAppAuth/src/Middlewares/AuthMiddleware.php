<?php
declare(strict_types=1);

namespace Modules\ForgeAppAuth\Middlewares;

use Modules\ForgeAppAuth\Services\UserContext;
use Modules\ForgeRouter\Helpers\Redirect;
use Modules\ForgeRouter\Http\Middleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Services\RedirectHandlerService;

#[Service]
#[RegisterMiddleware(group: 'auth')]
final class AuthMiddleware extends Middleware
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
