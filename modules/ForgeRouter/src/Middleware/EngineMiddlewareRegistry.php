<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Middleware;

/**
 * Registry of all kernel-provided middlewares with their default configurations.
 * This helps users discover available middlewares and understand their default groups and orders.
 */
final class EngineMiddlewareRegistry
{
    /**
     * Get all kernel-provided middlewares with their metadata.
     *
     * @return array<string, array{group: string, order: int, enabled: bool, description: string, class: string}>
     */
    public static function getMiddlewares(): array
    {
        return [
            // Global middlewares
            \Modules\ForgeRouter\Http\Middlewares\ObservabilityMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\ObservabilityMiddleware::class,
                'group' => 'global',
                'order' => -1,
                'enabled' => true,
                'description' => 'Captures request traces and performance data with minimal overhead',
            ],
            \Modules\ForgeRouter\Http\Middlewares\RateLimitMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\RateLimitMiddleware::class,
                'group' => 'global',
                'order' => 0,
                'enabled' => true,
                'description' => 'Rate limiting middleware to prevent abuse',
            ],
            \Modules\ForgeRouter\Http\Middlewares\CircuitBreakerMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\CircuitBreakerMiddleware::class,
                'group' => 'global',
                'order' => 1,
                'enabled' => true,
                'description' => 'Circuit breaker pattern for fault tolerance',
            ],
            \Modules\ForgeRouter\Http\Middlewares\CorsMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\CorsMiddleware::class,
                'group' => 'global',
                'order' => 2,
                'enabled' => true,
                'description' => 'Cross-Origin Resource Sharing (CORS) headers',
            ],
            \Modules\ForgeRouter\Http\Middlewares\SanitizeInputMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\SanitizeInputMiddleware::class,
                'group' => 'global',
                'order' => 3,
                'enabled' => false, // Disabled by default
                'description' => 'Sanitizes input data to prevent XSS attacks',
            ],
            \Modules\ForgeRouter\Http\Middlewares\CompressionMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\CompressionMiddleware::class,
                'group' => 'global',
                'order' => 4,
                'enabled' => true,
                'description' => 'Compresses response content (gzip, deflate)',
            ],

            // Web middlewares
            \Modules\ForgeRouter\Http\Middlewares\SessionMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\SessionMiddleware::class,
                'group' => 'web',
                'order' => 0,
                'enabled' => true,
                'description' => 'Session management for web requests',
            ],
            \Modules\ForgeRouter\Http\Middlewares\CsrfMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\CsrfMiddleware::class,
                'group' => 'web',
                'order' => 1,
                'enabled' => true,
                'description' => 'CSRF protection for web forms',
            ],
            \Modules\ForgeRouter\Http\Middlewares\RelaxSecurityHeadersMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\RelaxSecurityHeadersMiddleware::class,
                'group' => 'web',
                'order' => 3,
                'enabled' => true,
                'description' => 'Security headers (CSP, X-Frame-Options, etc.)',
            ],

            // API middlewares
            \Modules\ForgeRouter\Http\Middlewares\IpWhiteListMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\IpWhiteListMiddleware::class,
                'group' => 'api',
                'order' => 0,
                'enabled' => true,
                'description' => 'IP whitelist filtering for API endpoints',
            ],
            \Modules\ForgeRouter\Http\Middlewares\ApiKeyMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\ApiKeyMiddleware::class,
                'group' => 'api',
                'order' => 1,
                'enabled' => true,
                'description' => 'API key authentication',
            ],
            \Modules\ForgeRouter\Http\Middlewares\CookieMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\CookieMiddleware::class,
                'group' => 'api',
                'order' => 2,
                'enabled' => true,
                'description' => 'Cookie handling for API requests',
            ],
            \Modules\ForgeRouter\Http\Middlewares\ApiMiddleware::class => [
                'class' => \Modules\ForgeRouter\Http\Middlewares\ApiMiddleware::class,
                'group' => 'api',
                'order' => 2,
                'enabled' => true,
                'description' => 'API response formatting (XML, CSV, HTML, plain text)',
            ],
        ];
    }

    /**
     * Get middlewares by group.
     *
     * @param string $group The middleware group (e.g., 'global', 'web', 'api')
     * @return array<string, array{group: string, order: int, enabled: bool, description: string, class: string}>
     */
    public static function getMiddlewaresByGroup(string $group): array
    {
        return array_filter(
            self::getMiddlewares(),
            fn($middleware) => $middleware['group'] === $group
        );
    }

    /**
     * Get all middleware class names.
     *
     * @return array<string>
     */
    public static function getMiddlewareClasses(): array
    {
        return array_keys(self::getMiddlewares());
    }

    /**
     * Check if a class is an kernel middleware.
     *
     * @param string $className The middleware class name
     * @return bool True if it's an kernel middleware
     */
    public static function isEngineMiddleware(string $className): bool
    {
        return isset(self::getMiddlewares()[$className]);
    }
}
