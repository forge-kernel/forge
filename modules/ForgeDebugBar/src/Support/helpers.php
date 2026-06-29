<?php

use Modules\ForgeDebugBar\DebugBar;
use Forge\Core\DI\Container;
use Forge\Exceptions\MissingServiceException;
use Forge\Exceptions\ResolveParameterException;

if (!function_exists("add_timeline_event")) {
  function add_timeline_event(string $name, string $label, array $data = []): void
  {
    if (filter_var($_ENV["APP_DEBUG"] ?? false, FILTER_VALIDATE_BOOLEAN)) {
      try {
        $timelineCollector = Container::getInstance()->get(\Modules\ForgeRouter\Collectors\TimelineCollector::class);
        $timelineCollector->addEvent($name, $label, $data);
      } catch (\Throwable) {
      }
    }
  }
}

if (!function_exists("collect_view_data")) {
  function collect_view_data(string $view, mixed $data = []): void
  {
    try {
      $container = Container::getInstance();
      if ($container->has(\Modules\ForgeRouter\Collectors\ViewCollector::class)) {
        $viewCollector = $container->get(\Modules\ForgeRouter\Collectors\ViewCollector::class);
        $viewCollector->addView($view, $data);
      }
    } catch (\Throwable) {
    }
  }
}

if (!function_exists('formatBytes')) {
  function formatBytes(int $bytes): string
  {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
  }
}


