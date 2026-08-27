<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Contracts;

use Modules\ForgeSockets\Server\Frames\Opcode;

/**
 * The application-side half of a WebSocket connection. Implement this to
 * react to connections, messages and teardowns — the transport (handshake,
 * framing, heartbeat, close) is handled for you.
 */
interface MessageHandlerInterface
{
    /**
     * A new connection completed the handshake and (if an authenticator is
     * configured) passed authentication. `$connection->user()` is set.
     */
    public function onOpen(ConnectionInterface $connection): void;

    /**
     * A complete data frame (text or binary) arrived. Control frames (ping,
     * pong, close) are answered automatically and never reach this method.
     */
    public function onMessage(ConnectionInterface $connection, Opcode $opcode, string $payload): void;

    /**
     * The connection is fully torn down — the socket is closed. `$code` is a
     * WebSocket close code (1000 normal, 1001 going away, 1006 abnormal/EOF,
     * 1002/1008/1009 protocol failures, …).
     */
    public function onClose(ConnectionInterface $connection, int $code, string $reason): void;

    /**
     * A frame parse or handler error occurred on this connection.
     */
    public function onError(ConnectionInterface $connection, \Throwable $error): void;
}