<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter;

use App\Modules\ForgeRouter\Bootstrap\RouterSetup;
use App\Modules\ForgeRouter\Contracts\ErrorHandlerInterface;
use App\Modules\ForgeRouter\Events\RouterHookAttribute;
use App\Modules\ForgeRouter\Events\RouterHookManager;
use App\Modules\ForgeRouter\Events\RouterHookName;
use App\Modules\ForgeRouter\Http\Kernel;
use App\Modules\ForgeRouter\Http\Request;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\LifecycleHookName;
use Throwable;

#[Module(name: "ForgeRouter",
    description: "Forge Router and Http",
    author: "Forge Team",
    version: '1.0.0',
    type: "core",
    license: "MIT",
    tags: ["router", "http"],
    order: PHP_INT_MAX)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
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
            'enabled' => true,
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
    private static ?Kernel $kernel = null;

    public function register(Container $container): void
    {
        RouterHookManager::init();
    }

    #[LifecycleHook(hook: LifecycleHookName::APP_BOOTED)]
    public function boot(): void
    {
        RouterHookManager::discover();

        $container = Container::getInstance();

        $request = Request::createFromGlobals();
        $container->setInstance(Request::class, $request);

        RouterHookManager::triggerHook(RouterHookName::BEFORE_REQUEST, $request);

        try {
            $router = RouterSetup::setup($container);
            $kernel = new Kernel($router);
            self::$kernel = $kernel;

            $response = $kernel->handler($request);

            RouterHookManager::triggerHook(RouterHookName::AFTER_REQUEST, $request, $response);

            $response->send();

            RouterHookManager::triggerHook(RouterHookName::AFTER_RESPONSE, $request, $response);
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
