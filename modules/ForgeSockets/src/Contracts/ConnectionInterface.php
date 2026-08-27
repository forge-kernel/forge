<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Contracts;

/**
 * The view a handler gets of one live WebSocket connection. It can send text
 * or binary messages and close the connection; the transport owns everything
 * else (buffering, backpressure, control frames, the socket).
 */
interface ConnectionInterface
{
    public function id(): int;

    /**
     * The remote peer address ("127.0.0.1:52341"), empty when unknown.
     */
    public function peer(): string;

    /**
     * The request path from the opening handshake ("/ws/arcades/the-chess-hall"),
     * used to route the connection to its room.
     */
    public function path(): string;

    /**
     * The opaque user identifier resolved by the authenticator at handshake
     * time, or null when no authenticator is configured.
     */
    public function user(): ?string;

    /**
     * Queue a UTF-8 text frame to the client (written when the socket is
     * writable; the socket is closed if the send buffer overflows).
     */
    public function sendText(string $payload): void;

    /**
     * Queue a binary frame to the client.
     */
    public function sendBinary(string $payload): void;

    /**
     * Begin the closing handshake (queues a close frame, then closes once
     * flushed). No more data frames may be sent afterwards.
     */
    public function close(int $code = 1000, string $reason = ''): void;

    public function isOpen(): bool;

    public function isClosing(): bool;
}