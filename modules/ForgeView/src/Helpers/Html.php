<?php

declare(strict_types=1);

namespace Modules\ForgeView\Helpers;

final class Html
{
    public static function link(
        string $url,
        ?string $integrity = null,
        ?string $crossorigin = null
    ): string {
        $attributes = [
            "rel='stylesheet'",
            "href='{$url}'",
            "referrerpolicy='no-referrer'",
        ];

        if ($integrity) {
            $attributes[] = "integrity='{$integrity}'";
        }

        if ($crossorigin) {
            $attributes[] = "crossorigin='{$crossorigin}'";
        }

        return '<link ' . implode(' ', $attributes) . ' />';
    }

    public static function script(
        string $url,
        ?string $integrity = null,
        ?string $crossorigin = null,
        bool $defer = true
    ): string {
        $attributes = [
            "src='{$url}'",
            "referrerpolicy='no-referrer'",
        ];

        if ($defer) {
            $attributes[] = 'defer';
        }

        if ($integrity) {
            $attributes[] = "integrity='{$integrity}'";
        }

        if ($crossorigin) {
            $attributes[] = "crossorigin='{$crossorigin}'";
        }

        return '<script ' . implode(' ', $attributes) . '></script>';
    }
}
