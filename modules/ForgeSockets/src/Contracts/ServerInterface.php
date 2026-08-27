<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Contracts;

/**
 * The socket server entry point. Bind, then run the event loop until the
 * process is asked to stop.
 */
interface ServerInterface
{
    public function run(?callable $shouldStop = null): void;
}