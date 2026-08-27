<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Server\Frames;

/**
 * One decoded WebSocket frame. Payloads of fragmented messages are reassembled
 * by the {@see FrameParser}; every frame handed to a handler is complete.
 */
final readonly class Frame
{
    public function __construct(
        public bool $fin,
        public Opcode $opcode,
        public string $payload,
    ) {}

    public static function text(string $payload): self
    {
        return new self(true, Opcode::TEXT, $payload);
    }

    public static function binary(string $payload): self
    {
        return new self(true, Opcode::BINARY, $payload);
    }

    public static function ping(string $payload = ''): self
    {
        return new self(true, Opcode::PING, $payload);
    }

    public static function pong(string $payload = ''): self
    {
        return new self(true, Opcode::PONG, $payload);
    }

    public static function close(int $code, string $reason = ''): self
    {
        $payload = pack('n', $code) . $reason;

        return new self(true, Opcode::CLOSE, $payload);
    }
}