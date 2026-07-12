<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Modules\ForgeRouter\Http\Middleware as MiddlewareImpl;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Forge\Core\Session\SessionInterface;

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
            if (function_exists('collect_exception')) {
                try {
                    $container = \Forge\Core\DI\Container::getInstance();
                    if ($container->has(\Modules\ForgeRouter\Collectors\ExceptionCollector::class)) {
                        $collector = $container->get(\Modules\ForgeRouter\Collectors\ExceptionCollector::class);
                        $exceptions = $collector->getExceptions();
                        if (!empty($exceptions)) {
                            $_SESSION['_debugbar_exceptions'] = $exceptions;
                        } else {
                            unset($_SESSION['_debugbar_exceptions']);
                        }
                    }
                } catch (\Throwable) {
                }
            }
            $this->session->save();
        }

        return $response;
    }
}
