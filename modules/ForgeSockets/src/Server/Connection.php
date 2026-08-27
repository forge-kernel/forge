<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Server;

use Modules\ForgeSockets\Contracts\AuthenticatorInterface;
use Modules\ForgeSockets\Contracts\ConnectionInterface;
use Modules\ForgeSockets\Contracts\MessageHandlerInterface;
use Modules\ForgeSockets\Server\Frames\CloseCode;
use Modules\ForgeSockets\Server\Frames\Frame;
use Modules\ForgeSockets\Server\Frames\FrameCodec;
use Modules\ForgeSockets\Server\Frames\FrameParser;
use Modules\ForgeSockets\Server\Frames\Opcode;
use Modules\ForgeSockets\Server\Frames\ProtocolException;

/**
 * One live socket connection: handshake state machine, inbound frame dispatch
 * (control frames answered here, data frames handed to the handler) and a
 * bounded outbound send queue with backpressure.
 *
 * The connection is driven entirely by the event loop — read/write interest is
 * toggled through the `$writeNotifier` callback, and full teardown funnels
 * through `$onClosed` so the server owns registry/loop/handler cleanup exactly
 * once.
 */
final class Connection implements ConnectionInterface
{
    private const int HANDSHAKE_LIMIT = 16384;

    private const int MAX_SEND_BUFFER = 262144;

    private string $readBuffer = '';

    private string $sendQueue = '';

    private ?FrameParser $persistentParser = null;

    private bool $handshakeDone = false;

    private ConnectionState $state = ConnectionState::HANDSHAKING;

    private float $lastActivity;

    private ?string $user = null;

    private string $path = '';

    private int $closeCode = 0;

    private string $closeReason = '';

    /**
     * @param resource            $stream the non-blocking socket
     * @param \Closure(Connection): void $writeNotifier sync loop write interest
     * @param \Closure(Connection, int, string): void $onClosed teardown hook
     */
    public function __construct(
        private readonly int $id,
        private $stream,
        private readonly string $peer,
        private readonly MessageHandlerInterface $handler,
        private readonly ?AuthenticatorInterface $authenticator,
        private readonly int $maxPayload,
        private readonly int $maxSendBuffer,
        private readonly Handshake $handshake,
        private readonly ?\Closure $writeNotifier,
        private readonly ?\Closure $onClosed,
    ) {
        $this->lastActivity = microtime(true);
    }

    /**
     * The loop calls this with every chunk read off the socket.
     */
    public function feed(string $data, float $now): void
    {
        $this->lastActivity = $now;

        if ($this->state === ConnectionState::CLOSED || $this->state === ConnectionState::CLOSING) {
            return;
        }

        if (!$this->handshakeDone) {
            $this->readBuffer .= $data;
            if (strlen($this->readBuffer) > self::HANDSHAKE_LIMIT) {
                $this->finalize(CloseCode::MESSAGE_TOO_BIG, 'request too large');
                return;
            }

            $this->tryHandshake($now);
            if (!$this->handshakeDone) {
                return;
            }

            // The client may have sent the first frame in the same packet.
            $rest = $this->readBuffer;
            $this->readBuffer = '';
            if ($rest !== '') {
                $this->ingest($rest);
            }
            return;
        }

        $this->ingest($data);
    }

    public function flush(float $now): void
    {
        if ($this->sendQueue === '') {
            return;
        }

        $written = @fwrite($this->stream, $this->sendQueue);
        if ($written === false || $written === 0) {
            return; // would block — try again on the next loop iteration
        }

        $this->sendQueue = substr($this->sendQueue, $written);
        $this->lastActivity = $now;

        if ($this->sendQueue === '' && $this->state === ConnectionState::CLOSING) {
            $this->finalize($this->closeCode !== 0 ? $this->closeCode : CloseCode::NORMAL->value, $this->closeReason);
        }
    }

    public function id(): int
    {
        return $this->id;
    }

    public function peer(): string
    {
        return $this->peer;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function user(): ?string
    {
        return $this->user;
    }

    public function isOpen(): bool
    {
        return $this->state === ConnectionState::OPEN;
    }

    public function isClosing(): bool
    {
        return $this->state === ConnectionState::CLOSING;
    }

    public function isClosed(): bool
    {
        return $this->state === ConnectionState::CLOSED;
    }

    public function wantsWrite(): bool
    {
        return $this->sendQueue !== '';
    }

    public function lastActivity(): float
    {
        return $this->lastActivity;
    }

    /**
     * @return resource
     */
    public function stream()
    {
        return $this->stream;
    }

    public function sendText(string $payload): void
    {
        if ($this->state !== ConnectionState::OPEN) {
            return;
        }

        $this->enqueue(FrameCodec::encode(Frame::text($payload)));
    }

    public function sendBinary(string $payload): void
    {
        if ($this->state !== ConnectionState::OPEN) {
            return;
        }

        $this->enqueue(FrameCodec::encode(Frame::binary($payload)));
    }

    public function close(int $code = 1000, string $reason = ''): void
    {
        if ($this->state !== ConnectionState::OPEN) {
            return;
        }

        $this->closeCode = $code;
        $this->closeReason = $reason;
        $this->state = ConnectionState::CLOSING;
        $this->enqueue(FrameCodec::encode(Frame::close($code, $reason)));
    }

    /**
     * Heartbeat: ping a possibly-stale peer.
     */
    public function ping(): void
    {
        if ($this->state === ConnectionState::OPEN) {
            $this->enqueue(FrameCodec::encode(Frame::ping()));
        }
    }

    /**
     * Server shutdown: begin the closing handshake.
     */
    public function gracefulClose(): void
    {
        if ($this->state === ConnectionState::OPEN) {
            $this->close(CloseCode::GOING_AWAY->value, 'server shutting down');
        }
    }

    // --- internals -----------------------------------------------------

    private function ingest(string $data): void
    {
        try {
            $frames = $this->persistentParser()->feed($data);
        } catch (ProtocolException $e) {
            $this->fail($e->closeCode(), $e->getMessage());
            return;
        }

        foreach ($frames as $frame) {
            if ($this->state !== ConnectionState::OPEN) {
                return;
            }
            $this->dispatch($frame);
        }
    }

    private function dispatch(Frame $frame): void
    {
        $opcode = $frame->opcode;

        if ($opcode->isControl()) {
            switch ($opcode) {
                case Opcode::PING:
                    $this->enqueue(FrameCodec::encode(Frame::pong($frame->payload)));
                    return;
                case Opcode::PONG:
                    return; // activity already tracked in feed()
                case Opcode::CLOSE:
                    $code = $this->closeCodeFromPayload($frame->payload);
                    $this->closeCode = $code;
                    $this->state = ConnectionState::CLOSING;
                    $this->enqueue(FrameCodec::encode(Frame::close($code)));
                    return;
                default:
                    return;
            }
        }

        try {
            $this->handler->onMessage($this, $opcode, $frame->payload);
        } catch (\Throwable $e) {
            $this->handler->onError($this, $e);
            $this->fail(CloseCode::INTERNAL_ERROR, 'handler error');
        }
    }

    private function tryHandshake(float $now): void
    {
        $result = $this->handshake->evaluate($this->readBuffer);
        if ($result === null) {
            return; // wait for more bytes
        }

        if (!$result->accepted) {
            $this->writeRaw("HTTP/1.1 400 Bad Request\r\nConnection: close\r\nContent-Length: 0\r\n\r\n");
            $this->finalize(CloseCode::PROTOCOL_ERROR, 'bad websocket handshake');
            return;
        }

        if ($this->authenticator !== null) {
            $this->user = $this->authenticator->authenticate($result->path, $result->headers);
            if ($this->user === null) {
                $this->writeRaw("HTTP/1.1 403 Forbidden\r\nConnection: close\r\nContent-Length: 0\r\n\r\n");
                $this->finalize(CloseCode::POLICY_VIOLATION, 'unauthorized');
                return;
            }
        }

        $this->handshakeDone = true;
        $this->state = ConnectionState::OPEN;
        $this->path = $result->path;

        // Strip the consumed request header; any bytes beyond it are the
        // client's first frame(s), which feed() will hand to the parser.
        $headerEnd = strpos($this->readBuffer, "\r\n\r\n");
        $this->readBuffer = $headerEnd === false ? '' : substr($this->readBuffer, $headerEnd + 4);

        $this->writeRaw($result->response);
        $this->lastActivity = $now;

        try {
            $this->handler->onOpen($this);
        } catch (\Throwable $e) {
            $this->handler->onError($this, $e);
            $this->fail(CloseCode::INTERNAL_ERROR, 'handler open error');
        }
    }

    private function persistentParser(): FrameParser
    {
        return $this->persistentParser ??= new FrameParser(
            maxPayload: $this->maxPayload,
            requireMask: true,
        );
    }

    private function enqueue(string $bytes): void
    {
        if ($this->state === ConnectionState::CLOSED) {
            return;
        }

        $this->sendQueue .= $bytes;
        if (strlen($this->sendQueue) > $this->maxSendBuffer) {
            $this->sendQueue = '';
            $this->fail(CloseCode::POLICY_VIOLATION, 'send buffer exceeded');
            return;
        }

        $this->notifyWrite();
    }

    /**
     * Fail the connection now (protocol violation / overflow): queue a close
     * frame and let the flush close the socket, or hard-close when nothing
     * can be written.
     */
    private function fail(CloseCode $code, string $reason): void
    {
        if ($this->state === ConnectionState::CLOSED) {
            return;
        }

        $this->closeCode = $code->value;
        $this->closeReason = $reason;
        $this->state = ConnectionState::CLOSING;
        $this->sendQueue = FrameCodec::encode(Frame::close($code->value, $reason));

        if ($this->writeRaw($this->sendQueue)) {
            $this->sendQueue = '';
            $this->finalize($code->value, $reason);
            return;
        }

        $this->notifyWrite();
    }

    private function writeRaw(string $bytes): bool
    {
        if (!is_resource($this->stream)) {
            return false;
        }

        $written = @fwrite($this->stream, $bytes);
        if ($written === false) {
            return false;
        }

        if ($written < strlen($bytes)) {
            $this->sendQueue = substr($bytes, $written) . $this->sendQueue;
            $this->notifyWrite();
            return false;
        }

        return true;
    }

    private function notifyWrite(): void
    {
        if ($this->writeNotifier !== null) {
            ($this->writeNotifier)($this);
        }
    }

    /**
     * Extract the close code from an incoming close frame's payload (2-byte
     * code + optional reason); defaults to 1000/1005 when absent or malformed.
     */
    private function closeCodeFromPayload(string $payload): int
    {
        if (strlen($payload) < 2) {
            return CloseCode::NORMAL->value;
        }

        $code = unpack('n', substr($payload, 0, 2))[1];

        return $code;
    }

    private function finalize(int $code, string $reason): void
    {
        if ($this->state === ConnectionState::CLOSED) {
            return;
        }

        $this->state = ConnectionState::CLOSED;
        if ($this->onClosed !== null) {
            ($this->onClosed)($this, $code, $reason);
        }
    }

    /**
     * Mark the socket gone without a clean close (EOF/abrupt) — reports 1006.
     */
    public function markAborted(): void
    {
        $this->finalize(CloseCode::ABNORMAL->value, 'connection aborted');
    }
}