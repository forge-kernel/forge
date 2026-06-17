<?php

declare(strict_types=1);

namespace App\Modules\ForgeUi;

final class DesignTokens
{
    private static array $cache = [];

    public static function button(string $variant = 'primary', string $size = 'md'): array
    {
        $key = "button:{$variant}:{$size}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['inline-flex', 'items-center', 'justify-center', 'font-medium', 'transition-colors', 'focus:outline-none', 'focus:ring-2', 'focus:ring-offset-2', 'disabled:opacity-50', 'disabled:pointer-events-none'];

        $variants = [
            'primary' => ['bg-gray-900', 'text-white', 'hover:bg-gray-800', 'focus:ring-gray-900'],
            'secondary' => ['bg-white', 'text-gray-700', 'border', 'border-gray-300', 'hover:bg-gray-50', 'focus:ring-gray-900'],
            'danger' => ['bg-red-600', 'text-white', 'hover:bg-red-700', 'focus:ring-red-500'],
            'success' => ['bg-green-600', 'text-white', 'hover:bg-green-700', 'focus:ring-green-500'],
            'warning' => ['bg-yellow-600', 'text-white', 'hover:bg-yellow-700', 'focus:ring-yellow-500'],
            'info' => ['bg-blue-600', 'text-white', 'hover:bg-blue-700', 'focus:ring-blue-500'],
            'neutral' => ['bg-gray-200', 'text-gray-900', 'hover:bg-gray-300', 'focus:ring-gray-400'],
            'outline' => ['border', 'border-gray-300', 'bg-white', 'text-gray-700', 'hover:bg-gray-50', 'focus:ring-gray-900'],
            'ghost' => ['bg-transparent', 'text-gray-700', 'hover:bg-gray-800/5', 'focus:ring-gray-500'],
        ];

        $sizes = [
            'xs' => ['text-xs', 'px-2', 'py-1', 'rounded'],
            'sm' => ['text-sm', 'px-3', 'py-1.5', 'rounded'],
            'md' => ['text-sm', 'px-4', 'py-2.5', 'rounded-lg'],
            'lg' => ['text-base', 'px-6', 'py-3', 'rounded-lg'],
            'xl' => ['text-lg', 'px-6', 'py-3', 'rounded-lg'],
        ];

        $result = array_merge($base, $variants[$variant] ?? $variants['primary'], $sizes[$size] ?? $sizes['md']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function buttonState(string $state): array
    {
        $states = [
            'disabled' => ['opacity-50', 'cursor-not-allowed'],
            'loading' => ['relative', 'text-transparent'],
        ];
        return $states[$state] ?? [];
    }

    public static function input(string $variant = 'default', string $size = 'md'): array
    {
        $key = "input:{$variant}:{$size}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['block', 'w-full', 'border', 'transition-colors', 'focus:outline-none', 'focus:ring-2', 'focus:ring-offset-0'];

        $variants = [
            'default' => ['border-gray-300', 'focus:border-transparent', 'focus:ring-gray-900'],
            'error' => ['border-red-300', 'focus:border-transparent', 'focus:ring-red-500'],
            'success' => ['border-green-300', 'focus:border-transparent', 'focus:ring-green-500'],
        ];

        $sizes = [
            'sm' => ['text-sm', 'px-3', 'py-1.5', 'rounded-lg'],
            'md' => ['text-sm', 'px-4', 'py-2.5', 'rounded-lg'],
            'lg' => ['text-base', 'px-4', 'py-2.5', 'rounded-lg'],
        ];

        $result = array_merge($base, $variants[$variant] ?? $variants['default'], $sizes[$size] ?? $sizes['md']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function card(string $variant = 'default'): array
    {
        $key = "card:{$variant}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['bg-white', 'rounded-xl', 'shadow-sm', 'border', 'border-gray-200'];

        $variants = [
            'default' => [],
            'elevated' => ['shadow-lg'],
            'outlined' => [],
            'flat' => ['shadow-none', 'border-0'],
        ];

        $result = array_merge($base, $variants[$variant] ?? $variants['default']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function table(string $variant = 'default'): array
    {
        $key = "table:{$variant}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['min-w-full', 'divide-y', 'divide-gray-200'];

        $variants = [
            'default' => [],
            'striped' => [],
            'bordered' => ['border', 'border-gray-200'],
        ];

        $result = array_merge($base, $variants[$variant] ?? $variants['default']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function badge(string $variant = 'primary', string $size = 'md'): array
    {
        $key = "badge:{$variant}:{$size}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['inline-flex', 'items-center', 'font-medium'];

        $variants = [
            'primary' => ['bg-blue-100', 'text-blue-800'],
            'secondary' => ['bg-gray-100', 'text-gray-800'],
            'danger' => ['bg-red-100', 'text-red-800'],
            'success' => ['bg-green-100', 'text-green-800'],
            'warning' => ['bg-yellow-100', 'text-yellow-800'],
            'info' => ['bg-blue-100', 'text-blue-800'],
            'neutral' => ['bg-gray-100', 'text-gray-800'],
        ];

        $sizes = [
            'xs' => ['text-xs', 'px-1.5', 'py-0.5', 'rounded'],
            'sm' => ['text-xs', 'px-2', 'py-0.5', 'rounded'],
            'md' => ['text-sm', 'px-2.5', 'py-0.5', 'rounded-md'],
            'lg' => ['text-base', 'px-3', 'py-1', 'rounded-md'],
        ];

        $result = array_merge($base, $variants[$variant] ?? $variants['primary'], $sizes[$size] ?? $sizes['md']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function avatar(string $size = 'md'): array
    {
        $key = "avatar:{$size}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['inline-flex', 'items-center', 'justify-center', 'font-medium', 'text-white', 'bg-gray-500', 'overflow-hidden'];

        $sizes = [
            'xs' => ['w-6', 'h-6', 'text-xs', 'rounded'],
            'sm' => ['w-8', 'h-8', 'text-sm', 'rounded'],
            'md' => ['w-10', 'h-10', 'text-base', 'rounded-md'],
            'lg' => ['w-12', 'h-12', 'text-lg', 'rounded-md'],
            'xl' => ['w-16', 'h-16', 'text-xl', 'rounded-lg'],
        ];

        $result = array_merge($base, $sizes[$size] ?? $sizes['md']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function spinner(string $variant = 'default', string $size = 'md'): array
    {
        $key = "spinner:{$variant}:{$size}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['animate-spin', 'rounded-full', 'border-solid', 'border-t-transparent'];

        $variants = [
            'default' => ['border-blue-600'],
            'primary' => ['border-blue-600'],
            'white' => ['border-white'],
            'gray' => ['border-gray-600'],
        ];

        $sizes = [
            'xs' => ['w-3', 'h-3', 'border'],
            'sm' => ['w-4', 'h-4', 'border'],
            'md' => ['w-6', 'h-6', 'border-2'],
            'lg' => ['w-8', 'h-8', 'border-2'],
            'xl' => ['w-12', 'h-12', 'border-4'],
        ];

        $result = array_merge($base, $variants[$variant] ?? $variants['default'], $sizes[$size] ?? $sizes['md']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function modal(string $size = 'md'): array
    {
        $key = "modal:{$size}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['fixed', 'inset-0', 'z-50', 'flex', 'items-center', 'justify-center', 'p-4'];

        $sizes = [
            'sm' => [],
            'md' => [],
            'lg' => [],
            'xl' => [],
            '2xl' => [],
            'full' => [],
        ];

        $result = array_merge($base, $sizes[$size] ?? $sizes['md']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function modalContent(string $size = 'md'): array
    {
        $key = "modalContent:{$size}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['bg-white', 'rounded-lg', 'shadow-xl', 'max-h-[90vh]', 'overflow-auto'];

        $sizes = [
            'sm' => ['max-w-sm', 'w-full'],
            'md' => ['max-w-md', 'w-full'],
            'lg' => ['max-w-lg', 'w-full'],
            'xl' => ['max-w-xl', 'w-full'],
            '2xl' => ['max-w-2xl', 'w-full'],
            'full' => ['max-w-full', 'w-full', 'm-4'],
        ];

        $result = array_merge($base, $sizes[$size] ?? $sizes['md']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function alert(string $variant = 'info'): array
    {
        $key = "alert:{$variant}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['rounded-md', 'border-l-4', 'p-4'];

        $variants = [
            'success' => ['bg-green-50', 'border-green-200', 'text-green-800'],
            'error' => ['bg-red-50', 'border-red-200', 'text-red-800'],
            'warning' => ['bg-yellow-50', 'border-yellow-200', 'text-yellow-800'],
            'info' => ['bg-blue-50', 'border-blue-200', 'text-blue-800'],
        ];

        $result = array_merge($base, $variants[$variant] ?? $variants['info']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function color(string $name): string
    {
        $colors = [
            'primary' => 'blue',
            'secondary' => 'gray',
            'danger' => 'red',
            'success' => 'green',
            'warning' => 'yellow',
            'info' => 'blue',
            'neutral' => 'gray',
        ];
        return $colors[$name] ?? 'gray';
    }

    public static function spacing(string $size): string
    {
        $spacings = [
            'xs' => '0.25rem',
            'sm' => '0.5rem',
            'md' => '1rem',
            'lg' => '1.5rem',
            'xl' => '2rem',
            '2xl' => '3rem',
        ];
        return $spacings[$size] ?? '1rem';
    }

    public static function shadow(string $size = 'md'): array
    {
        $shadows = [
            'sm' => ['shadow-sm'],
            'md' => ['shadow'],
            'lg' => ['shadow-lg'],
            'xl' => ['shadow-xl'],
            '2xl' => ['shadow-2xl'],
            'none' => ['shadow-none'],
        ];
        return $shadows[$size] ?? $shadows['md'];
    }

    public static function radius(string $size = 'md'): array
    {
        $radiuses = [
            'none' => ['rounded-none'],
            'sm' => ['rounded'],
            'md' => ['rounded-md'],
            'lg' => ['rounded-lg'],
            'xl' => ['rounded-xl'],
            'full' => ['rounded-full'],
        ];
        return $radiuses[$size] ?? $radiuses['md'];
    }

    public static function drawer(string $position = 'right'): array
    {
        $key = "drawer:{$position}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['fixed', 'z-50', 'bg-white', 'shadow-xl', 'transform', 'transition-transform'];

        $positions = [
            'left' => ['left-0', 'top-0', 'bottom-0'],
            'right' => ['right-0', 'top-0', 'bottom-0'],
            'top' => ['top-0', 'left-0', 'right-0'],
            'bottom' => ['bottom-0', 'left-0', 'right-0'],
        ];

        $result = array_merge($base, $positions[$position] ?? $positions['right']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function tabs(string $orientation = 'horizontal'): array
    {
        $key = "tabs:{$orientation}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['fw-tabs'];

        $orientations = [
            'horizontal' => [],
            'vertical' => ['flex', 'flex-row'],
        ];

        $result = array_merge($base, $orientations[$orientation] ?? $orientations['horizontal']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function accordion(): array
    {
        return ['fw-accordion', 'space-y-2'];
    }

    public static function progress(string $type = 'linear'): array
    {
        $key = "progress:{$type}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['fw-progress', 'w-full', 'bg-gray-200', 'rounded-full', 'overflow-hidden'];

        $types = [
            'linear' => ['h-2'],
            'circular' => [],
        ];

        $result = array_merge($base, $types[$type] ?? $types['linear']);
        self::$cache[$key] = $result;
        return $result;
    }

    public static function toast(string $variant = 'info'): array
    {
        $key = "toast:{$variant}";
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $base = ['fw-toast', 'flex', 'items-center', 'p-4', 'rounded-lg', 'shadow-lg', 'mb-2', 'min-w-[300px]', 'max-w-md'];

        $variants = [
            'success' => ['bg-green-50', 'border', 'border-green-200', 'text-green-800'],
            'error' => ['bg-red-50', 'border', 'border-red-200', 'text-red-800'],
            'warning' => ['bg-yellow-50', 'border', 'border-yellow-200', 'text-yellow-800'],
            'info' => ['bg-blue-50', 'border', 'border-blue-200', 'text-blue-800'],
        ];

        $result = array_merge($base, $variants[$variant] ?? $variants['info']);
        self::$cache[$key] = $result;
        return $result;
    }
}
