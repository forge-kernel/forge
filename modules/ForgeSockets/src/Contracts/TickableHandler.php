<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Contracts;

/**
 * Optional capability for handlers that want a periodic tick (roughly 1/s)
 * while the event loop runs — used for game clocks, turn timeouts and
 * scheduled match advancement. The server calls onTick only when the handler
 * implements this interface.
 */
interface TickableHandler
{
    public function onTick(float $now): void;
}