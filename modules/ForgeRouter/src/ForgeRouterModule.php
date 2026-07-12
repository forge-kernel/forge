<?php

declare(strict_types=1);

namespace Modules\ForgeRouter;

use Modules\ForgeRouter\Bootstrap\RouterSetup;
use Modules\ForgeRouter\Contracts\ErrorHandlerInterface;
use Modules\ForgeRouter\Events\RouterHookManager;
use Modules\ForgeRouter\Events\RouterHookName;
use Modules\ForgeRouter\Http\Kernel;
use Modules\ForgeRouter\Http\Request;
use Forge\Core\Debug\Metrics;
use Forge\Core\DI\Container;
use Modules\ForgeRouter\Collectors;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\LifecycleHookName;
use Forge\Core\ResetManager;
use Modules\ForgeRouter\Middleware\MiddlewareRegistrar;
use Throwable;

#[Module(name: "ForgeRouter",
    description: "Forge Router and Http",
    author: "Forge Team",
    version: '1.0.28',
    type: "core",
    license: "MIT",
    tags: ["router", "http"],
    order: PHP_INT_MAX)]
#[Compatibility(framework: '>=6.0.23', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    'forge_router' => [
        'cors' => [
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization'],
        ],
        'rate_limit' => [
            'enabled' => true,
            'max_requests' => 40,
            'time_window' => 60,
            'disable_in_dev' => true,
            'bypass_ips' => ['127.0.0.1', '::1', 'localhost'],
        ],
        'circuit_breaker' => [
            'max_failures' => 5,
            'reset_time' => 300,
            'disable_in_dev' => true,
        ],
        'csp' => [
            'enabled' => false,
            'directives' => [
                'default-src' => ["'self'"],
                'script-src' => ["'self'", "'unsafe-inline'"],
                'style-src' => ["'self'", "'unsafe-inline'"],
            ],
        ],
        'ip_whitelist' => [],
    ],
])]
#[PostInstall(command: "modules:forge-router:init", args: ["--force"])]
#[PostUninstall(command: "modules:forge-router:cleanup", args: ["--force"])]
final class ForgeRouterModule
{
    use MiddlewareRegistrar;

    private static ?Kernel $kernel = null;

    public function register(Container $container): void
    {
        RouterHookManager::init();
        self::registerEngineMiddlewares();
        self::registerCollectors($container);
    }

    private static function registerCollectors(Container $container): void
    {
        $collectors = [
            Collectors\TimelineCollector::class,
            Collectors\ViewCollector::class,
            Collectors\ExceptionCollector::class,
            Collectors\DatabaseCollector::class,
        ];

        foreach ($collectors as $collector) {
            if (!$container->has($collector)) {
                $container->singleton($collector, $collector);
            }
        }
    }

    private static function registerEngineMiddlewares(): void
    {
        $essential = [
            \Modules\ForgeRouter\Http\Middlewares\ObservabilityMiddleware::class => ['global', -1],
            \Modules\ForgeRouter\Http\Middlewares\RateLimitMiddleware::class => ['global', 0],
            \Modules\ForgeRouter\Http\Middlewares\CircuitBreakerMiddleware::class => ['global', 1],
            \Modules\ForgeRouter\Http\Middlewares\SanitizeInputMiddleware::class => ['global', 3],
            \Modules\ForgeRouter\Http\Middlewares\SessionMiddleware::class => ['web', 0],
            \Modules\ForgeRouter\Http\Middlewares\CsrfMiddleware::class => ['web', 1],
        ];

        foreach ($essential as $class => [$group, $order]) {
            self::registerMiddleware($class, $group, $order);
        }
    }

    #[LifecycleHook(hook: LifecycleHookName::APP_BOOTED)]
    public function boot(): void
    {
        // Skip routing when the setup/maintenance panel is active
        if (defined('FORGE_SETUP_MODE')) {
            return;
        }

        ResetManager::triggerBefore();

        Metrics::start("router_hook_discover");
        RouterHookManager::discover();
        Metrics::stop("router_hook_discover");

        $container = Container::getInstance();

        Metrics::start("router_request_create");
        $request = Request::createFromGlobals();
        $container->setInstance(Request::class, $request);
        Metrics::stop("router_request_create");

        if (function_exists('add_timeline_event')) {
            add_timeline_event('request.received', 'lifecycle', [
                'method' => $request->getMethod(),
                'uri' => $request->getUri(),
            ]);
        }

        try {
            Metrics::start("router_setup");
            $router = RouterSetup::setup($container);
            Metrics::stop("router_setup");

            $kernel = new Kernel($router);
            self::$kernel = $kernel;

            Metrics::start("router_before_request_hook");
            RouterHookManager::triggerHook(RouterHookName::BEFORE_REQUEST, $request);
            Metrics::stop("router_before_request_hook");

            Metrics::start("router_kernel_handler");
            $response = $kernel->handler($request);
            Metrics::stop("router_kernel_handler");

            if (function_exists('add_timeline_event')) {
                add_timeline_event('response.created', 'lifecycle', [
                    'status' => $response->getStatusCode(),
                ]);
            }

            Metrics::start("router_after_request_hook");
            RouterHookManager::triggerHook(RouterHookName::AFTER_REQUEST, $request, $response);
            Metrics::stop("router_after_request_hook");

            Metrics::start("router_response_send");
            $response->send();
            Metrics::stop("router_response_send");

            if (function_exists('add_timeline_event')) {
                add_timeline_event('response.sent', 'lifecycle');
            }

            Metrics::start("router_after_response_hook");
            RouterHookManager::triggerHook(RouterHookName::AFTER_RESPONSE, $request, $response);
            Metrics::stop("router_after_response_hook");

            ResetManager::triggerAfter();
        } catch (Throwable $e) {
            self::handleException($e);
        }

        exit;
    }

    public static function getKernel(): ?Kernel
    {
        return self::$kernel;
    }

    private static function handleException(Throwable $e): void
    {
        if (function_exists('collect_exception')) {
            collect_exception($e);
        }

        $container = Container::getInstance();
        $request = Request::createFromGlobals();

        try {
            if ($container->has(ErrorHandlerInterface::class)) {
                $errorHandler = $container->get(ErrorHandlerInterface::class);
                if ($errorHandler instanceof ErrorHandlerInterface) {
                    $response = $errorHandler->handle($e, $request);
                    $response->send();
                    return;
                }
            }

            $errorHandlers = $container->getAll(ErrorHandlerInterface::class);
            if (!empty($errorHandlers)) {
                $response = $errorHandlers[0]->handle($e, $request);
                $response->send();
                return;
            }
        } catch (Throwable $fatal) {
            http_response_code(500);
            if (ini_get('display_errors')) {
                echo "Fatal error: " . $fatal->getMessage() . "\n";
                echo "Original error: " . $e->getMessage() . "\n";
            } else {
                echo "An error occurred.";
            }
            exit(1);
        }

        throw $e;
    }

}
