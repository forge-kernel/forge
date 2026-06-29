<?php

namespace Modules\ForgeDebugBar\Collectors;

use Modules\ForgeRouter\Routing\Router;

class RouteCollector implements CollectorInterface
{
  private array $routesData = [];

  public static function collect(...$args): array
  {
    return self::instance()->collectRoutes();
  }

  public static function instance(): self
  {
    static $instance = null;
    if (null === $instance) {
      $instance = new self();
    }
    return $instance;
  }

  public function collectRoutes(): array
  {
    /** @var Router $router */
    $router = Router::getInstance();
    $currentRoute = $router->getCurrentRoute();

    if ($currentRoute) {
      $handler = 'N/A';
      if (isset($currentRoute['controller'], $currentRoute['method'])) {
        $handlerArray = [$currentRoute['controller'], $currentRoute['method']];
        $handler = $this->formatHandler($handlerArray);
      }

      return [
        'uri' => $currentRoute['uri'] ?? 'N/A',
        'method' => $currentRoute['http_method'] ?? 'N/A',
        'handler' => $handler,
        'middleware' => $currentRoute['middleware'] ?? [],
      ];
    } else {
      return ['message' => 'No current route matched.'];
    }
  }

  private function formatHandler(array|callable $handler): string
  {
    if (is_callable($handler)) {
      if (is_array($handler)) {
        if (is_string($handler[0])) {
          return $handler[0] . '::' . $handler[1];
        } else {
          return 'Closure in ' . get_class($handler[0]) . '->' . $handler[1];
        }
      } else {
        $reflection = new \ReflectionFunction($handler);
        return 'Closure in ' . $reflection->getFileName() . ':' . $reflection->getStartLine();
      }
    } elseif (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
      return $handler[0] . '::' . $handler[1];
    } else {
      return 'Unknown Handler Type';
    }
  }
}
