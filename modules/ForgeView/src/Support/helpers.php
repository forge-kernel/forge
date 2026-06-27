<?php

declare(strict_types=1);

use Forge\Core\Contracts\ViewInterface;
use Forge\Core\DI\Container;
use Forge\Core\Helpers\Html;
use Forge\Core\Module\ModuleResourceResolver;
use Forge\Core\Security\AssetRegistry;

if (!function_exists("component")) {
    function component(string $name, array|object|null $props = [], array $slots = []): string
    {
        $reference = ModuleResourceResolver::parse($name);
        $view = Container::getInstance()->get(ViewInterface::class);
        return $view->viewComponent($reference->name, $props, $reference->module ?? null, $slots);
    }
}

if (!function_exists("slot")) {
    function slot(string $name = 'default', string $default = ''): string
    {
        return Container::getInstance()->get(ViewInterface::class)->slot($name, $default);
    }
}

if (!function_exists('form_open')) {
    function form_open(string $action = '', string $method = 'POST', array $attrs = []): string
    {
        $method = strtoupper($method);
        $realMethod = in_array($method, ['GET', 'POST']) ? $method : 'POST';

        $attrString = '';
        foreach ($attrs as $key => $value) {
            $attrString .= sprintf(' %s="%s"', htmlspecialchars($key), htmlspecialchars($value));
        }

        $html = sprintf('<form action="%s" method="%s"%s>', htmlspecialchars($action), $realMethod, $attrString);

        if (function_exists('csrf_input')) {
            $html .= csrf_input();
        }

        if ($realMethod === 'POST' && $method !== 'POST') {
            $html .= sprintf('<input type="hidden" name="_method" value="%s">', htmlspecialchars($method));
        }

        return $html;
    }
}

if (!function_exists('form_close')) {
    /**
     * Close the form tag.
     */
    function form_close(): string
    {
        return '</form>';
    }
}

if (!function_exists('external_asset_config')) {
    function external_asset_config(
        string $name,
        string $url,
        ?string $integrity = null,
        ?string $crossorigin = null
    ): array {
        return [
            $name => array_filter([
                'url' => $url,
                'integrity' => $integrity,
                'crossorigin' => $crossorigin,
            ]),
        ];
    }
}

if (!function_exists('external_asset')) {
    function external_asset(string $name): string
    {
        $asset = config("security.csp.external_assets.$name") ?? config("forge_router.csp.external_assets.$name");

        if (!$asset) {
            throw new RuntimeException(
                "External asset [$name] not defined. " .
                "Did you forget to unpack external_asset_config() with ... ?"
            );
        }

        $url = $asset['url'];
        $type = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

        AssetRegistry::registerExternal($asset);

        return match ($type) {
            'css' => Html::link(
                $url,
                $asset['integrity'] ?? null,
                $asset['crossorigin'] ?? null
            ),
            'js' => Html::script(
                $url,
                $asset['integrity'] ?? null,
                $asset['crossorigin'] ?? null
            ),
            default => throw new RuntimeException("Unsupported external asset type [$type]."),
        };
    }
}

if (!function_exists('merge_classes')) {
    function merge_classes(array|string $base, array|string $additional = [], array|string $overrides = []): string
    {
        $flatten = function ($input) use (&$flatten) {
            if (is_string($input)) {
                return array_filter(explode(' ', trim($input)));
            }
            if (!is_array($input)) {
                return [];
            }
            $result = [];
            foreach ($input as $item) {
                if (is_array($item)) {
                    $result = array_merge($result, $flatten($item));
                } elseif (is_string($item) && !empty(trim($item))) {
                    $result[] = trim($item);
                }
            }
            return array_filter($result);
        };

        $baseClasses = $flatten($base);
        $additionalClasses = $flatten($additional);
        $overrideClasses = $flatten($overrides);

        $merged = array_merge($baseClasses, $additionalClasses, $overrideClasses);

        return implode(' ', array_unique($merged));
    }
}
