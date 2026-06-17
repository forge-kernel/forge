<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Routing;

use App\Modules\ForgeRouter\Contracts\RouteScopeFilterInterface;
use Forge\Core\Debug\Metrics;
use Forge\Core\DI\Container;
use Forge\Core\Helpers\ModuleHelper;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Attributes\ApiRoute;
use App\Modules\ForgeRouter\Http\Attributes\RequiresRole;
use App\Modules\ForgeRouter\Attributes\Layout;
use Forge\Exceptions\MissingServiceException;
use ReflectionClass;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use ReflectionMethod;

final class Router
{
    use ResponseHelper;

    private static ?self $instance = null;
    private array $middlewareGroups;
    /** @var array<string, array{controller: class-string, method: string, handler: array, params: array, middleware: array, permissions: array, roles: array, layout: ?string, preferred: bool}> */
    private array $routes = [];
    /** @var array<string, array{controller: class-string, method: string, handler: array, params: array, middleware: array, permissions: array, roles: array, layout: ?string, preferred: bool}> */
    private array $staticRoutes = [];
    /** @var array<string, array{controller: class-string, method: string, handler: array, params: array, middleware: array, permissions: array, roles: array, layout: ?string, preferred: bool, regex: string}> */
    private array $dynamicRoutes = [];
    private Container $container;
    private ?array $currentRoute = null;
    /** @var array<string, array{reflection: ReflectionMethod, parameters: array<int, array{name: string, type: ?string, hasType: bool, isRequest: bool}>}> */
    private static array $reflectionCache = [];
    /** @var array<string, RadixTree> */
    private array $radixTrees = [];
    /** @var array<string, callable> Pre-computed middleware pipelines keyed by route hash */
    private array $pipelineCache = [];

    private function __construct(
        Container $container,
        array $middlewareConfig = [],
    ) {
        $this->container = $container;
        $this->middlewareGroups = $middlewareConfig;
        $this->radixTrees = [
            "GET" => new RadixTree(),
            "POST" => new RadixTree(),
            "PUT" => new RadixTree(),
            "PATCH" => new RadixTree(),
            "DELETE" => new RadixTree(),
        ];
    }

    /**
     * @throws \Exception
     */
    public static function getInstance(): Router
    {
        if (self::$instance === null) {
            throw new \Exception(
                "Router not initialized. Use RouterSetup::setup() first.",
            );
        }
        return self::$instance;
    }

    public static function init(
        Container $container,
        array $middlewareConfig = [],
    ): Router {
        if (self::$instance === null) {
            self::$instance = new self($container, $middlewareConfig);
        }
        return self::$instance;
    }

    public function getCurrentRoute(): array|null
    {
        return $this->currentRoute;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Auto-register controllers (recursively) and **skip** wrong-scope ones when route scope filter is available.
     * @throws \ReflectionException
     */
    public function registerControllers(string $controllerClass): void
    {
        $moduleName = ModuleHelper::extractModuleNameFromNamespace(
            $controllerClass,
        );
        if (
            $moduleName !== null &&
            ModuleHelper::isModuleDisabled($moduleName)
        ) {
            return;
        }

        $reflection = new ReflectionClass($controllerClass);

        $scopeFilter = $this->getRouteScopeFilter();

        if ($scopeFilter === null) {
            $this->registerAll($reflection);
            return;
        }

        $onCentral = $scopeFilter::isCentralDomain();

        $ctrlScope = $scopeFilter->extractScope($reflection);
        if ($ctrlScope && !$scopeFilter->allowedHere($ctrlScope, $onCentral)) {
            return;
        }

        foreach ($reflection->getMethods() as $method) {
            $methScope = $scopeFilter->extractScope($method) ?? $ctrlScope;
            if (
                $methScope &&
                !$scopeFilter->allowedHere($methScope, $onCentral)
            ) {
                continue;
            }

            $this->registerMethodRoutes($method, $controllerClass);
        }
    }

    private function registerAll(ReflectionClass $reflection): void
    {
        foreach ($reflection->getMethods() as $method) {
            $this->registerMethodRoutes($method, $reflection->getName());
        }
    }

    private function registerMethodRoutes(
        ReflectionMethod $method,
        string $controllerClass,
    ): void {
        $routeAttributes = array_merge(
            $method->getAttributes(Route::class),
            $method->getAttributes(ApiRoute::class),
        );

        $attr = new ReflectionClass($controllerClass);
        $middlewareAttributes = array_merge(
            $attr->getAttributes(Middleware::class),
            $method->getAttributes(Middleware::class),
        );

        $roleAttributes = array_merge(
            $attr->getAttributes(RequiresRole::class),
            $method->getAttributes(RequiresRole::class),
        );

        $roles = [];
        foreach ($roleAttributes as $roleAttr) {
            $roles[] = $roleAttr->newInstance()->role;
        }
        $roles = array_unique($roles);

        $layout = null;
        $classLayoutAttributes = $attr->getAttributes(Layout::class);
        $methodLayoutAttributes = $method->getAttributes(Layout::class);
        foreach ($classLayoutAttributes as $layoutAttr) {
            $layout = $layoutAttr->newInstance()->name;
        }
        foreach ($methodLayoutAttributes as $layoutAttr) {
            $layout = $layoutAttr->newInstance()->name;
        }

        $middleware = [];
        foreach ($middlewareAttributes as $attr) {
            $instance = $attr->newInstance();
            foreach ($instance->middleware as $mwName) {
                $middleware = array_merge(
                    $middleware,
                    $this->middlewareGroups[$mwName] ?? [$mwName],
                );
            }
        }

        foreach ($routeAttributes as $attr) {
            $route = $attr->newInstance();
            $routeMiddlewares = $this->resolveMiddlewareGroups(
                $route->middleware,
            );
            $middleware = array_merge($middleware, $routeMiddlewares);
            $permissions = $route->permissions;

            $paramNames = [];
            $pattern = preg_replace_callback(
                "/\{([a-zA-Z0-9_]+)(?::(.+))?\}/",
                function ($m) use (&$paramNames) {
                    $paramNames[] = $m[1];
                    $constraint = $m[2] ?? "";
                    if ($constraint === ".+") {
                        return "(.+)";
                    } elseif (
                        $constraint === "[^/]+" ||
                        $constraint === "no-slash"
                    ) {
                        return "([^/]+)";
                    }
                    return "([a-zA-Z0-9_-]+)";
                },
                $route->path,
            );
            $regex = "#^{$pattern}/?$#";

            $routeData = [
                "controller" => $controllerClass,
                "method" => $method->getName(),
                "handler" => [$controllerClass, $method->getName()],
                "params" => $paramNames,
                "middleware" => $middleware,
                "permissions" => $permissions,
                "roles" => $roles,
                "layout" => $layout,
                "preferred" => $route->preferred,
            ];

            if (empty($paramNames)) {
                $staticKey = $route->method . ":" . $route->path;
                if (isset($this->staticRoutes[$staticKey])) {
                    if (
                        !empty($this->staticRoutes[$staticKey]["preferred"]) &&
                        !$route->preferred
                    ) {
                        continue;
                    }
                }
                $this->staticRoutes[$staticKey] = $routeData;

                $key = $route->method . $regex;
                $this->routes[$key] = $routeData;
            } else {
                if (!isset($this->dynamicRoutes[$route->method])) {
                    $this->dynamicRoutes[$route->method] = [];
                }

                if (isset($this->dynamicRoutes[$route->method][$regex])) {
                    if (
                        !empty(
                            $this->dynamicRoutes[$route->method][$regex][
                                "preferred"
                            ]
                        ) &&
                        !$route->preferred
                    ) {
                        continue;
                    }
                }

                $routeDataWithRegex = array_merge($routeData, [
                    "regex" => $regex,
                ]);
                $this->dynamicRoutes[$route->method][
                    $regex
                ] = $routeDataWithRegex;

                $key = $route->method . $regex;
                $this->routes[$key] = $routeData;

                $methodUpper = strtoupper($route->method);
                if (isset($this->radixTrees[$methodUpper])) {
                    $this->radixTrees[$methodUpper]->add(
                        $route->path,
                        $routeData,
                    );
                }
            }
        }
    }

    private function resolveMiddlewareGroups(array $groups): array
    {
        $middlewares = [];
        foreach ($groups as $group) {
            $middlewares = array_merge(
                $middlewares,
                $this->middlewareGroups[$group] ?? [],
            );
        }
        return $middlewares;
    }

    /**
     * Get route scope filter from container if available.
     *
     * @return RouteScopeFilterInterface|null The route scope filter, or null if not available
     */
    private function getRouteScopeFilter(): ?RouteScopeFilterInterface
    {
        try {
            if ($this->container->has(RouteScopeFilterInterface::class)) {
                $filter = $this->container->get(
                    RouteScopeFilterInterface::class,
                );
                if ($filter instanceof RouteScopeFilterInterface) {
                    return $filter;
                }
            }

            $filters = $this->container->getAll(
                RouteScopeFilterInterface::class,
            );
            if (!empty($filters)) {
                return $filters[0];
            }
        } catch (\Throwable $e) {
            error_log(
                "Failed to discover route scope filter: " . $e->getMessage(),
            );
        }

        return null;
    }

    public function dispatch(Request $request): mixed
    {
        Metrics::start("routing_dispatching");
        $uri = $request->serverParams["REQUEST_URI"] ?? "/";
        $method = $request->getMethod();
        $path = parse_url($uri, PHP_URL_PATH);
        $path = $path !== false ? $path : "/";

        $normalizedPath = $path;
        if ($path !== "/" && str_ends_with($path, "/")) {
            $normalizedPath = rtrim($path, "/");
        }

        $route = null;
        $this->currentRoute = null;

        $staticKey = $method . ":" . $normalizedPath;
        if (isset($this->staticRoutes[$staticKey])) {
            $route = $this->staticRoutes[$staticKey];
            $route["uri"] = $normalizedPath;
            $route["http_method"] = $method;
            $this->currentRoute = $route;
            $request->setAttribute("_route", $route);
        } else {
            $staticKey = $method . ":" . $path;
            if (isset($this->staticRoutes[$staticKey])) {
                $route = $this->staticRoutes[$staticKey];
                $route["uri"] = $path;
                $route["http_method"] = $method;
                $this->currentRoute = $route;
                $request->setAttribute("_route", $route);
            } else {
                $methodUpper = strtoupper($method);
                if (isset($this->radixTrees[$methodUpper])) {
                    $radixResult = $this->radixTrees[$methodUpper]->find(
                        $normalizedPath,
                    );
                }
                if ($radixResult !== null) {
                    $route = $radixResult;
                    $route["uri"] = $normalizedPath;
                    $route["http_method"] = $method;
                    $this->currentRoute = $route;
                    $request->setAttribute("_route", $route);
                } else {
                    $methodRoutes = $this->dynamicRoutes[$method] ?? [];
                    foreach ($methodRoutes as $regex => $routeInfo) {
                        if (preg_match($regex, $normalizedPath, $matches)) {
                            $routeInfo["regex_matches"] = $matches;
                            $route = $routeInfo;
                            $route["uri"] = $normalizedPath;
                            $route["http_method"] = $method;
                            unset($route["regex"]);
                            $this->currentRoute = $route;
                            $request->setAttribute("_route", $route);
                            break;
                        }
                    }
                }
            }
        }

        if ($route === null) {
            $errorCode = 404;
            require_once BASE_PATH . "/modules/ForgeRouter/src/Templates/error_page.php";
            return $this->createErrorResponse($request, "", (int) $errorCode);
        }

        $globalMiddlewares = $this->middlewareGroups["global"] ?? [];
        $allMiddlewares = array_merge(
            $globalMiddlewares,
            $route["middleware"] ?? [],
        );
        $permissions = $route["permissions"] ?? [];
        $request->setAttribute("required_permissions", $permissions);

        $roles = $route["roles"] ?? [];
        $request->setAttribute("required_roles", $roles);

        $pipelineKey =
            $route["http_method"] .
            ":" .
            $route["uri"] .
            "|" .
            implode(",", $allMiddlewares);

        if (!isset($this->pipelineCache[$pipelineKey])) {
            $this->pipelineCache[$pipelineKey] = array_reduce(
                array_reverse($allMiddlewares),
                fn($next, $mw) => fn($req) => $this->container
                    ->make($mw)
                    ->handle($req, $next),
                fn($req) => $this->runController($route, $req),
            );
        }

        Metrics::stop("routing_dispatching");
        return $this->pipelineCache[$pipelineKey]($request);
    }

    /**
     * @throws \ReflectionException
     * @throws MissingServiceException
     */
    private function runController(array $route, Request $request): mixed
    {
        $controllerClass = $route["controller"];
        $methodName = $route["method"];
        $params = [];
        $arguments = [];

        $reflectionData = $this->getReflectionData(
            $controllerClass,
            $methodName,
        );
        $reflectionMethod = $reflectionData["reflection"];
        $parameterMetadata = $reflectionData["parameters"];

        $routeParams = [];

        if (isset($route["params"]) && isset($route["regex_matches"])) {
            $matches = $route["regex_matches"];
            array_shift($matches);
            foreach ($route["params"] as $index => $name) {
                $routeParams[$name] = $matches[$index] ?? null;
            }
        } elseif (
            isset($route["params"]) &&
            is_array($route["params"]) &&
            !array_is_list($route["params"])
        ) {
            $routeParams = $route["params"];
        }

        foreach ($parameterMetadata as $paramMeta) {
            if ($paramMeta["isRequest"]) {
                $arguments[] = $request;
                continue;
            }

            $value = $routeParams[$paramMeta["name"]] ?? null;
            if ($paramMeta["hasType"] && $value !== null) {
                $type = $paramMeta["type"];
                if ($type === "int") {
                    $value = (int) $value;
                } elseif ($type === "float") {
                    $value = (float) $value;
                } elseif ($type === "bool") {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
            }
            $arguments[] = $value;
        }

        $controllerInstance = $this->container->make($controllerClass);
        return $controllerInstance->$methodName(...$arguments);
    }

    /**
     * Get cached reflection data for a controller method.
     * Caches ReflectionMethod and parameter metadata to avoid reflection overhead on every request.
     *
     * @param string $controllerClass
     * @param string $methodName
     * @return array{reflection: ReflectionMethod, parameters: array<int, array{name: string, type: ?string, hasType: bool, isRequest: bool}>}
     * @throws \ReflectionException
     */
    private function getReflectionData(
        string $controllerClass,
        string $methodName,
    ): array {
        $cacheKey = $controllerClass . "::" . $methodName;

        if (!isset(self::$reflectionCache[$cacheKey])) {
            $reflectionMethod = new ReflectionMethod(
                $controllerClass,
                $methodName,
            );
            $parameters = [];

            foreach ($reflectionMethod->getParameters() as $param) {
                $type = $param->getType();
                $typeName = null;
                $hasType = false;

                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();
                    $hasType = true;
                }

                $parameters[] = [
                    "name" => $param->getName(),
                    "type" => $typeName,
                    "hasType" => $hasType,
                    "isRequest" => $param->getName() === "request",
                ];
            }

            self::$reflectionCache[$cacheKey] = [
                "reflection" => $reflectionMethod,
                "parameters" => $parameters,
            ];
        }

        return self::$reflectionCache[$cacheKey];
    }
}
