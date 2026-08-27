<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Handlers;

use Modules\ForgeSockets\Contracts\ConnectionInterface;
use Modules\ForgeSockets\Contracts\MessageHandlerInterface;
use Modules\ForgeSockets\Server\Frames\Opcode;

/**
 * The default handler: echoes every text message back. Lets the primitive be
 * exercised end-to-end before any application (games, chat, presence) plugs a
 * real handler in.
 */
final class EchoHandler implements MessageHandlerInterface
{
    public function onOpen(ConnectionInterface $connection): void
    {
        $connection->sendText('{"event":"open","id":' . $connection->id() . '}');
    }

    public function onMessage(ConnectionInterface $connection, Opcode $opcode, string $payload): void
    {
        if ($opcode === Opcode::TEXT) {
            $connection->sendText($payload);
        }
    }

    public function onClose(ConnectionInterface $connection, int $code, string $reason): void
    {
        // no-op
    }

    public function onError(ConnectionInterface $connection, \Throwable $error): void
    {
        // no-op
    }
}