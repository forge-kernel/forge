<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Server;

use Modules\ForgeSockets\Contracts\AuthenticatorInterface;
use Modules\ForgeSockets\Contracts\MessageHandlerInterface;
use Modules\ForgeSockets\Contracts\ServerInterface;
use Modules\ForgeSockets\Contracts\TickableHandler;
use Modules\ForgeSockets\Server\Frames\CloseCode;

/**
 * The RFC 6455 server: bind a TCP listener, accept connections, run them on a
 * shared non-blocking event loop with a heartbeat, and tear everything down
 * gracefully on shutdown. Handlers and authenticators are injected — this
 * primitive has no knowledge of rooms, games or users.
 */
final class WebSocketServer implements ServerInterface
{
    private const int READ_CHUNK = 8192;

    /** @var resource|null */
    private $listen = null;

    private ?EventLoop $loop = null;

    private readonly ConnectionRegistry $connections;

    private int $nextId = 1;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly MessageHandlerInterface $handler,
        private readonly ?AuthenticatorInterface $authenticator = null,
        private readonly int $maxPayload = 65536,
        private readonly int $heartbeatSeconds = 30,
        private readonly int $maxSendBuffer = 262144,
        /** Handler tick cadence in seconds (1.0 turn-based; realtime hubs lower
         *  this — the hub's own scheduler decides what each room actually does). */
        private readonly float $tickInterval = 1.0,
    ) {
        $this->connections = new ConnectionRegistry();
    }

    public function run(?callable $shouldStop = null): void
    {
        $this->bind();

        $loop = new EventLoop();
        $this->loop = $loop;

        $loop->addRead($this->listen, function (): void {
            $this->accept();
        });

        // Self-rescheduling heartbeat: each tick re-arms the next one.
        $heartbeat = null;
        $heartbeat = function () use ($loop, &$heartbeat): void {
            $this->heartbeat();
            $loop->addTimer($this->heartbeatSeconds, $heartbeat);
        };
        $loop->addTimer($this->heartbeatSeconds, $heartbeat);

        // Optional handler tick for game clocks + scheduled work. The cadence
        // is configurable so realtime hubs can drive the sim scheduler faster
        // than the turn-based default of 1/s.
        if ($this->handler instanceof TickableHandler) {
            $tick = null;
            $tick = function () use ($loop, &$tick): void {
                $this->handler->onTick(microtime(true));
                $loop->addTimer($this->tickInterval, $tick);
            };
            $loop->addTimer($this->tickInterval, $tick);
        }

        try {
            $loop->run($shouldStop);
        } finally {
            $this->loop = null;
        }

        $this->shutdown($loop);
    }

    // --- listeners -----------------------------------------------------

    /**
     * Bind the TCP listener. Idempotent — callers (tests, diagnostics) may
     * bind explicitly to learn the bound address, then run.
     */
    public function bind(): void
    {
        if (is_resource($this->listen)) {
            return;
        }

        $this->listen = @stream_socket_server(
            "tcp://{$this->host}:{$this->port}",
            $errno,
            $errstr,
        );

        if ($this->listen === false) {
            throw new \RuntimeException(
                "Unable to bind {$this->host}:{$this->port}: {$errstr} ({$errno})",
            );
        }

        stream_set_blocking($this->listen, false);
    }

    /**
     * The bound address ("127.0.0.1:8282") once bound, '' before.
     */
    public function boundAddress(): string
    {
        if (!is_resource($this->listen)) {
            return '';
        }

        $name = stream_socket_get_name($this->listen, false);

        return $name === false ? '' : $name;
    }

    private function accept(): void
    {
        if ($this->listen === null) {
            return;
        }

        $peer = @stream_socket_accept($this->listen, 0, $peerName);
        if ($peer === false) {
            return;
        }

        stream_set_blocking($peer, false);

        $id = $this->nextId++;
        $loop = $this->loop;
        if ($loop === null) {
            @fclose($peer);
            return;
        }

        $connection = new Connection(
            id: $id,
            stream: $peer,
            peer: is_string($peerName) ? $peerName : '',
            handler: $this->handler,
            authenticator: $this->authenticator,
            maxPayload: $this->maxPayload,
            maxSendBuffer: $this->maxSendBuffer,
            handshake: new Handshake(),
            writeNotifier: fn (Connection $c): bool => $this->syncWriteInterest($loop, $c),
            onClosed: function (Connection $c, int $code, string $reason) use ($loop): void {
                $this->teardown($loop, $c, $code, $reason);
            },
        );

        $this->connections->add($connection);
        $loop->addRead($peer, function () use ($connection): void {
            $this->onReadable($connection);
        });
        $loop->addWrite($peer, function () use ($connection): void {
            $connection->flush(microtime(true));
            $this->syncWriteInterest($this->loop, $connection);
        });
    }

    private function onReadable(Connection $connection): void
    {
        $data = @fread($connection->stream(), self::READ_CHUNK);
        if ($data === false || $data === '') {
            $connection->markAborted();
            return;
        }

        $connection->feed($data, microtime(true));
    }

    private function syncWriteInterest(EventLoop $loop, Connection $connection): bool
    {
        $loop->setWriteInterest($connection->stream(), $connection->wantsWrite());

        return true;
    }

    private function teardown(EventLoop $loop, Connection $connection, int $code, string $reason): void
    {
        if (!$connection->isClosed()) {
            return;
        }

        $this->connections->remove($connection->id());
        $loop->remove($connection->stream());
        @fclose($connection->stream());

        try {
            $this->handler->onClose($connection, $code, $reason);
        } catch (\Throwable $e) {
            $this->handler->onError($connection, $e);
        }
    }

    private function heartbeat(): void
    {
        $now = microtime(true);

        foreach ($this->connections->all() as $connection) {
            $idle = $now - $connection->lastActivity();

            if ($idle > $this->heartbeatSeconds * 2) {
                $connection->markAborted();
            } elseif ($idle > $this->heartbeatSeconds) {
                $connection->ping();
            }
        }
    }

    private function shutdown(EventLoop $loop): void
    {
        foreach ($this->connections->all() as $connection) {
            $connection->gracefulClose();
            $connection->flush(microtime(true));
        }

        foreach ($this->connections->all() as $connection) {
            if (!$connection->isClosed()) {
                $connection->markAborted();
            }
        }

        $loop->remove($this->listen);
        if (is_resource($this->listen)) {
            @fclose($this->listen);
        }
    }
}