<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Middleware;

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
            \App\Modules\ForgeRouter\Http\Middlewares\RateLimitMiddleware::class => [
                'class' => \App\Modules\ForgeRouter\Http\Middlewares\RateLimitMiddleware::class,
                'group' => 'global',
                'order' => 0,
                'enabled' => true,
                'description' => 'Rate limiting middleware to prevent abuse',
            ],
            \App\Modules\ForgeRouter\Http\Middlewares\CircuitBreakerMiddleware::class => [
                'class' => \App\Modules\ForgeRouter\Http\Middlewares\CircuitBreakerMiddleware::class,
                'group' => 'global',
                'order' => 1,
                'enabled' => true,
                'description' => 'Circuit breaker pattern for fault tolerance',
            ],
            \App\Modules\ForgeRouter\Http\Middlewares\CorsMiddleware::class => [
                'class' => \App\Modules\ForgeRouter\Http\Middlewares\CorsMiddleware::class,
                'group' => 'global',
                'order' => 2,
                'enabled' => true,
                'description' => 'Cross-Origin Resource Sharing (CORS) headers',
            ],
            \App\Modules\ForgeRouter\Http\Middlewares\SanitizeInputMiddleware::class => [
                'class' => \App\Modules\ForgeRouter\Http\Middlewares\SanitizeInputMiddleware::class,
                'group' => 'global',
                'order' => 3,
                'enabled' => false, // Disabled by default
                'description' => 'Sanitizes input data to prevent XSS attacks',
            ],
            \App\Modules\ForgeRouter\Http\Middlewares\CompressionMiddleware::class => [
                'class' => \App\Modules\ForgeRouter\Http\Middlewares\CompressionMiddleware::class,
                'group' => 'global',
                'order' => 4,
                'enabled' => true,
                'description' => 'Compresses response content (gzip, deflate)',
            ],

            // Web middlewares
            \App\Modules\ForgeRouter\Http\Middlewares\SessionMiddleware::class => [
                'class' => \App\Modules\ForgeRouter\Http\Middlewares\SessionMiddleware::class,
                'group' => 'web',
                'order' => 0,
                'enabled' => true,
                'description' => 'Session management for web requests',
            ],
            \App\Modules\ForgeRouter\Http\Middlewares\CsrfMiddleware::class => [
                'class' => \App\Modules\ForgeRouter\Http\Middlewares\CsrfMiddleware::class,
                'group' => 'web',
                'order' => 1,
                'enabled' => true,
                'description' => 'CSRF protection for web forms',
            ],
            \App\Modules\ForgeRouter\Http\Middlewares\RelaxSecurityHeadersMiddleware::class => [
                'class' => \App\Modules\ForgeRouter\Http\Middlewares\RelaxSecurityHeadersMiddleware::class,
                'group' => 'web',
                'order' => 3,
                'enabled' => true,
                'description' => 'Security headers (CSP, X-Frame-Options, etc.)',
            ],

            // API middlewares
            \App\Modules\ForgeRouter\Http\Middlewares\IpWhiteListMiddleware::class => [
                'class' => \App\Modules\ForgeRouter\Http\Middlewares\IpWhiteListMiddleware::class,
                'group' => 'api',
                'order' => 0,
                'enabled' => true,
                'description' => 'IP whitelist filtering for API endpoints',
            ],
            \App\Modules\ForgeRouter\Http\Middlewares\ApiKeyMiddleware::class => [
                'class' => \App\Modules\ForgeRouter\Http\Middlewares\ApiKeyMiddleware::class,
                'group' => 'api',
                'order' => 1,
                'enabled' => true,
                'description' => 'API key authentication',
            ],
            \App\Modules\ForgeRouter\Http\Middlewares\CookieMiddleware::class => [
                'class' => \App\Modules\ForgeRouter\Http\Middlewares\CookieMiddleware::class,
                'group' => 'api',
                'order' => 2,
                'enabled' => true,
                'description' => 'Cookie handling for API requests',
            ],
            \App\Modules\ForgeRouter\Http\Middlewares\ApiMiddleware::class => [
                'class' => \App\Modules\ForgeRouter\Http\Middlewares\ApiMiddleware::class,
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
