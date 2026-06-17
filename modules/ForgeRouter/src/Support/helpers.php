<?php

declare(strict_types=1);

use App\Modules\ForgeRouter\Collectors\DatabaseCollector;
use App\Modules\ForgeRouter\Collectors\ExceptionCollector;
use App\Modules\ForgeRouter\Collectors\TimelineCollector;
use App\Modules\ForgeRouter\Collectors\ViewCollector;
use App\Modules\ForgeRouter\Http\Request;
use Forge\Core\DI\Container;

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
