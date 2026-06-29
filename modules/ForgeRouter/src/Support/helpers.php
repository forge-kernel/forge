<?php

declare(strict_types=1);

use Modules\ForgeRouter\Collectors\DatabaseCollector;
use Modules\ForgeRouter\Collectors\ExceptionCollector;
use Modules\ForgeRouter\Collectors\TimelineCollector;
use Modules\ForgeRouter\Collectors\ViewCollector;
use Modules\ForgeRouter\Http\Request;
use Forge\Core\DI\Container;
use Forge\Core\Services\TokenManager;

if (!function_exists('request')) {
    function request(): Request
    {
        return Container::getInstance()->get(Request::class);
    }
}

if (!function_exists('is_link_active')) {
    function is_link_active(string $path, array|string|null $class = null): bool|string
    {
        $current = rtrim(parse_url(request()->getUri(), PHP_URL_PATH), '/');
        $normalizedPath = rtrim($path, '/');

        $active = $normalizedPath === $current;

        if (!$active && str_ends_with($normalizedPath, '*')) {
            $prefix = rtrim($normalizedPath, '*');
            $active = str_starts_with($current, $prefix);
        }

        if ($class === null) {
            return $active;
        }

        if (!$active) {
            return '';
        }

        if (is_string($class)) {
            return $class;
        }

        return implode(' ', $class);
    }
}

if (!function_exists('add_timeline_event')) {
    function add_timeline_event(string $name, string $label = 'event', array $data = []): void
    {
        try {
            $container = Container::getInstance();
            if ($container->has(TimelineCollector::class)) {
                $collector = $container->get(TimelineCollector::class);
                $collector->addEvent($name, $label, $data);
            }
        } catch (\Throwable $e) {
        }
    }
}

if (!function_exists('collect_view_data')) {
    function collect_view_data(string $viewPath, array|object $data = []): void
    {
        try {
            $container = Container::getInstance();
            if ($container->has(ViewCollector::class)) {
                $collector = $container->get(ViewCollector::class);
                $collector->addView($viewPath, $data);
            }
        } catch (\Throwable $e) {
        }
    }
}

if (!function_exists('collect_database_query')) {
    function collect_database_query(
        string $query,
        array $bindings = [],
        float $time = 0.0,
        string $connectionName = 'default',
        string $origin = ''
    ): void {
        try {
            $container = Container::getInstance();
            if ($container->has(DatabaseCollector::class)) {
                $collector = $container->get(DatabaseCollector::class);
                $collector->addQuery($query, $bindings, $time, $connectionName, $origin);
            }
        } catch (\Throwable $e) {
        }
    }
}

if (!function_exists('collect_exception')) {
    function collect_exception(Throwable $exception): void
    {
        try {
            $container = Container::getInstance();
            if ($container->has(ExceptionCollector::class)) {
                $collector = $container->get(ExceptionCollector::class);
                $collector->addException($exception);
            }
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        $mgr = Container::getInstance()->make(TokenManager::class);
        return $mgr->getToken("web");
    }
}

if (!function_exists('csrf_meta')) {
    function csrf_meta(): string
    {
        return '<meta name="csrf-token" content="' .
            htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") .
            '">';
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input(): string
    {
        return '<input type="hidden" name="_token" value="' .
            htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") .
            '">';
    }
}

if (!function_exists('window_csrf_token')) {
    function window_csrf_token(): string
    {
        return "<script>
        window.csrfToken = document.querySelector('meta[name='csrf-token']')?.getAttribute('content') || '';
        </script>";
    }
}
