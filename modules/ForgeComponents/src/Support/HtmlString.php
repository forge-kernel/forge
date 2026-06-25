<?php

declare(strict_types=1);

namespace App\Modules\ForgeComponents\Support;

/**
 * A stringable wrapper that signals the contained HTML is safe to render raw.
 *
 * Use this when you need to pass HTML markup through components that would
 * otherwise escape plain strings (e.g. table cells that contain badges or links).
 */
final readonly class HtmlString implements \Stringable
{
    public function __construct(
        public string $html
    ) {
    }

    public function __toString(): string
    {
        return $this->html;
    }
}
