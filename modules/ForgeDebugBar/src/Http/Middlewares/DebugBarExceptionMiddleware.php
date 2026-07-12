<?php

declare(strict_types=1);

namespace Modules\ForgeDebugBar\Http\Middlewares;

use Modules\ForgeRouter\Http\Middleware as MiddlewareImpl;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Forge\Core\DI\Container;
use Modules\ForgeRouter\Collectors\ExceptionCollector;

class DebugBarExceptionMiddleware extends MiddlewareImpl
{
    public function handle(Request $request, callable $next): Response
    {
        try {
            $response = $next($request);
        } finally {
            $this->mergeAndPersistExceptions();
        }

        return $response;
    }

    private function mergeAndPersistExceptions(): void
    {
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                return;
            }

            $container = Container::getInstance();
            if (!$container->has(ExceptionCollector::class)) {
                return;
            }

            $collector = $container->get(ExceptionCollector::class);

            $pending = $_SESSION['_debugbar_exceptions'] ?? [];
            unset($_SESSION['_debugbar_exceptions']);

            $currentForSession = $collector->getExceptions();

            if (!empty($pending)) {
                $collector->mergeExceptions($pending);
            }

            if (!empty($currentForSession)) {
                $_SESSION['_debugbar_exceptions'] = $currentForSession;
            }
        } catch (\Throwable) {
        }
    }
}
