<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Tests;

use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Forge\Core\DI\Container;
use ReflectionClass;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Routing\Route;
use Modules\ForgeRouter\Routing\Router;
use Modules\ForgeRouter\Http\Attributes\ApiRoute;

#[Group('routing')]
final class RouterTest extends TestCase
{
    private function getRouter(): Router
    {
        $container = Container::getInstance();
        if (!$container) {
            $container = new Container();
            $containerReflection = new ReflectionClass(Container::class);
            if ($containerReflection->hasProperty('instance')) {
                $prop = $containerReflection->getProperty('instance');
                $prop->setAccessible(true);
                $prop->setValue(null, $container);
            }
        }
        $container->bind(MockController::class, MockController::class);

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);

        $router = Router::init($container, []);
        $router->registerControllers(MockController::class);
        $router->registerControllers(RoutableMockController::class);
        $router->registerControllers(PrefixedRoutableMockController::class);

        return $router;
    }

    #[Test('Router resolves static route correctly')]
    public function resolves_static_route(): void
    {
        $router = $this->getRouter();
        $routes = $router->getRoutes();
        $this->assertTrue(isset($routes['GET#^/test-static/?$#']));
        $this->assertEquals(MockController::class, $routes['GET#^/test-static/?$#']['controller']);
    }

    #[Test('Router resolves dynamic route correctly')]
    public function resolves_dynamic_route(): void
    {
        $router = $this->getRouter();
        $routes = $router->getRoutes();

        $routeKey = 'GET#^/test-dynamic/([a-zA-Z0-9_-]+)/?$#';
        $this->assertTrue(isset($routes[$routeKey]));
        $this->assertEquals(['id'], $routes[$routeKey]['params']);
    }

    #[Test('Router resolves API route correctly')]
    public function resolves_api_route(): void
    {
        $router = $this->getRouter();
        $routes = $router->getRoutes();
        $this->assertTrue(isset($routes['POST#^/api/v1/api/submit/?$#']));
    }

    #[Test('Router resolves Endpoint route from Routable controller')]
    public function resolves_routable_endpoint(): void
    {
        $router = $this->getRouter();
        $routes = $router->getRoutes();
        $this->assertTrue(isset($routes['GET#^/routable-endpoint/?$#']));
        $this->assertEquals(RoutableMockController::class, $routes['GET#^/routable-endpoint/?$#']['controller']);
    }

    #[Test('Router resolves Endpoint dynamic route from Routable controller')]
    public function resolves_routable_dynamic_endpoint(): void
    {
        $router = $this->getRouter();
        $routes = $router->getRoutes();
        $this->assertTrue(isset($routes['GET#^/routable-dynamic/([a-zA-Z0-9_-]+)/?$#']));
        $this->assertEquals(['slug'], $routes['GET#^/routable-dynamic/([a-zA-Z0-9_-]+)/?$#']['params']);
    }

    #[Test('Router still resolves deprecated Route')]
    public function resolves_deprecated_route(): void
    {
        $router = $this->getRouter();
        $routes = $router->getRoutes();
        $this->assertTrue(isset($routes['GET#^/test-static/?$#']));
        $this->assertEquals(MockController::class, $routes['GET#^/test-static/?$#']['controller']);
    }

    #[Test('Router resolves index Endpoint under Routable prefix')]
    public function resolves_prefixed_routable_index(): void
    {
        $router = $this->getRouter();
        $routes = $router->getRoutes();
        $this->assertTrue(isset($routes['GET#^/users/?$#']));
        $this->assertEquals(PrefixedRoutableMockController::class, $routes['GET#^/users/?$#']['controller']);
    }

    #[Test('Router resolves dynamic Endpoint under Routable prefix')]
    public function resolves_prefixed_routable_dynamic(): void
    {
        $router = $this->getRouter();
        $routes = $router->getRoutes();
        $routeKey = 'GET#^/users/([a-zA-Z0-9_-]+)/?$#';
        $this->assertTrue(isset($routes[$routeKey]));
        $this->assertEquals(['id'], $routes[$routeKey]['params']);
    }

    #[Test('Router resolves POST Endpoint under Routable prefix')]
    public function resolves_prefixed_routable_post(): void
    {
        $router = $this->getRouter();
        $routes = $router->getRoutes();
        $this->assertTrue(isset($routes['POST#^/users/?$#']));
        $this->assertEquals(PrefixedRoutableMockController::class, $routes['POST#^/users/?$#']['controller']);
    }
}

class MockController
{
    #[Route('/test-static', 'GET')]
    public function staticRoute(): string
    {
        return 'static';
    }

    #[Route('/test-dynamic/{id}', 'GET')]
    public function dynamicRoute(int $id): string
    {
        return 'dynamic:' . $id;
    }

    #[ApiRoute('/api/submit', 'POST')]
    public function submitRoute(): string
    {
        return 'submit';
    }
}

#[Routable]
class RoutableMockController
{
    #[Endpoint('/routable-endpoint', 'GET')]
    public function index(): string
    {
        return 'routable';
    }

    #[Endpoint('/routable-dynamic/{slug}', 'GET')]
    public function dynamicRoute(string $slug): string
    {
        return 'routable:' . $slug;
    }
}

#[Routable('/users')]
class PrefixedRoutableMockController
{
    #[Endpoint]
    public function index(): string
    {
        return 'users';
    }

    #[Endpoint('/{id}')]
    public function show(): string
    {
        return 'user';
    }

    #[Endpoint(method: 'POST')]
    public function create(): string
    {
        return 'created';
    }
}
