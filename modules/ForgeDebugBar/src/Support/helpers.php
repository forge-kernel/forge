<?php

use Modules\ForgeDebugBar\Collectors\MessageCollector;

if (!function_exists('debug_log')) {
    function debug_log(mixed $message, string $label = 'info'): void
    {
        try {
            MessageCollector::instance()->addMessage($message, $label);
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
