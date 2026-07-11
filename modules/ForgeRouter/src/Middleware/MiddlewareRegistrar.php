<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Middleware;

trait MiddlewareRegistrar
{
    /** @var array<string, array<int, array{class: class-string, group: string, order: int, overrideClass: ?string}>> */
    private static array $registeredMiddleware = [];

    public static function clearRegisteredMiddleware(): void
    {
        self::$registeredMiddleware = [];
    }

    public static function registerMiddleware(
        string $class,
        string $group = 'global',
        int $order = 500,
        ?string $overrideClass = null,
    ): void {
        self::$registeredMiddleware[$group][] = [
            'class' => $class,
            'group' => $group,
            'order' => $order,
            'overrideClass' => $overrideClass ?? $class,
        ];
    }

    /** @return array<string, array<int, array{class: class-string, group: string, order: int, overrideClass: ?string}>> */
    public static function getRegisteredMiddleware(): array
    {
        return self::$registeredMiddleware;
    }
}
