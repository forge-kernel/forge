<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Modules\ForgeRouter\Http\Middleware as MiddlewareImpl;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\Middleware;
use Forge\Core\Session\SessionInterface;

#[Middleware(group: 'web', order: 0, allowDuplicate: true, enabled: true)]
class SessionMiddleware extends MiddlewareImpl
{
    public function __construct(
        private readonly SessionInterface $session
    ) {
    }
    public function handle(Request $request, callable $next): Response
    {
        $sessionEnabled = $_ENV['SESSION_ENABLED'] ?? true;
        if ($sessionEnabled === false || $sessionEnabled === 'false') {
            return $next($request);
        }

        $this->session->start();

        try {
            $response = $next($request);
        } finally {
            $this->session->save();
        }

        return $response;
    }
}
