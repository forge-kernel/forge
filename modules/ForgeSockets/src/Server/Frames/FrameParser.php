<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Server\Frames;

/**
 * Incremental, server-side frame decoder (RFC 6455 §5).
 *
 * Feed it whatever bytes arrive off the socket; it buffers partial frames,
 * tolerates multiple frames per packet and frames split across packets, and
 * reassembles fragmented messages (continuation frames) into one complete
 * frame. Control frames are returned immediately, even mid-fragmentation.
 *
 * Client frames MUST be masked (rejected with 1002 otherwise). Violations
 * throw {@see ProtocolException} carrying the close code to use.
 */
final class FrameParser
{
    private string $buffer = '';

    private ?Opcode $openOpcode = null;

    private string $fragment = '';

    private int $fragmentCount = 0;

    public function __construct(
        private readonly int $maxPayload = 65536,
        private readonly int $maxFragments = 16,
        private readonly bool $requireMask = true,
    ) {}

    /**
     * Consume bytes and return every complete frame they produced.
     *
     * @return list<Frame>
     *
     * @throws ProtocolException on any framing violation
     */
    public function feed(string $data): array
    {
        $this->buffer .= $data;

        $frames = [];
        while (strlen($this->buffer) > 0) {
            $before = strlen($this->buffer);
            $frame = $this->tryParse();
            if ($frame !== null) {
                $frames[] = $frame;
            }
            if (strlen($this->buffer) === $before) {
                break; // no progress — need more bytes
            }
        }

        return $frames;
    }

    private function tryParse(): ?Frame
    {
        $length = strlen($this->buffer);
        if ($length < 2) {
            return null;
        }

        $b0 = ord($this->buffer[0]);
        $b1 = ord($this->buffer[1]);

        $fin = ($b0 & 0x80) !== 0;
        $rsv = $b0 & 0x70;
        $opcode = $b0 & 0x0F;
        $masked = ($b1 & 0x80) !== 0;
        $len7 = $b1 & 0x7F;

        if ($rsv !== 0) {
            throw new ProtocolException(CloseCode::PROTOCOL_ERROR, 'rsv bits set on frame');
        }

        $op = Opcode::tryFrom($opcode);
        if ($op === null) {
            throw new ProtocolException(CloseCode::PROTOCOL_ERROR, 'reserved opcode 0x' . dechex($opcode));
        }

        if ($this->requireMask && !$masked) {
            throw new ProtocolException(CloseCode::PROTOCOL_ERROR, 'client frames must be masked');
        }

        $offset = 2;
        if ($len7 === 126) {
            if ($length < 4) {
                return null;
            }
            $payloadLength = unpack('n', substr($this->buffer, 2, 2))[1];
            $offset = 4;
        } elseif ($len7 === 127) {
            if ($length < 10) {
                return null;
            }
            $payloadLength = unpack('J', substr($this->buffer, 2, 8))[1];
            $offset = 10;
            if ($payloadLength < 0 || $payloadLength > $this->maxPayload) {
                throw new ProtocolException(CloseCode::MESSAGE_TOO_BIG, 'frame length over max payload');
            }
        } else {
            $payloadLength = $len7;
        }

        if ($payloadLength > $this->maxPayload) {
            throw new ProtocolException(CloseCode::MESSAGE_TOO_BIG, 'frame over max payload (' . $payloadLength . ' bytes)');
        }

        if ($masked) {
            $maskOffset = $offset;
            $offset += 4;
            if ($length < $offset + $payloadLength) {
                return null;
            }
            $payload = FrameCodec::unmask(substr($this->buffer, $offset, $payloadLength), substr($this->buffer, $maskOffset, 4));
        } else {
            if ($length < $offset + $payloadLength) {
                return null;
            }
            $payload = substr($this->buffer, $offset, $payloadLength);
        }

        $this->buffer = substr($this->buffer, $offset + $payloadLength);

        if ($op->isControl()) {
            if (!$fin || $payloadLength > 125) {
                throw new ProtocolException(CloseCode::PROTOCOL_ERROR, 'control frames must be final and <= 125 bytes');
            }

            return new Frame(true, $op, $payload);
        }

        return $this->reassemble($fin, $op, $payload);
    }

    private function reassemble(bool $fin, Opcode $op, string $payload): ?Frame
    {
        if (!$fin) {
            // A new fragmented message, or a continuation of the open one.
            if ($op === Opcode::CONTINUATION) {
                if ($this->openOpcode === null) {
                    throw new ProtocolException(CloseCode::PROTOCOL_ERROR, 'continuation frame without an open message');
                }
            } else {
                if ($this->openOpcode !== null) {
                    throw new ProtocolException(CloseCode::PROTOCOL_ERROR, 'new data frame while a message is fragmented');
                }
                $this->openOpcode = $op;
            }

            $this->fragment .= $payload;
            $this->fragmentCount++;
            if (strlen($this->fragment) > $this->maxPayload) {
                throw new ProtocolException(CloseCode::MESSAGE_TOO_BIG, 'fragmented message over max payload');
            }
            if ($this->fragmentCount > $this->maxFragments) {
                throw new ProtocolException(CloseCode::MESSAGE_TOO_BIG, 'too many fragments in one message');
            }

            return null;
        }

        if ($op === Opcode::CONTINUATION) {
            if ($this->openOpcode === null) {
                throw new ProtocolException(CloseCode::PROTOCOL_ERROR, 'final continuation frame without an open message');
            }

            $this->fragment .= $payload;
            $complete = new Frame(true, $this->openOpcode, $this->fragment);
            $this->openOpcode = null;
            $this->fragment = '';
            $this->fragmentCount = 0;

            return $this->validateUtf8($complete);
        }

        return $this->validateUtf8(new Frame(true, $op, $payload));
    }

    private function validateUtf8(Frame $frame): Frame
    {
        if ($frame->opcode !== Opcode::TEXT) {
            return $frame;
        }

        if (preg_match('//u', $frame->payload) !== 1) {
            throw new ProtocolException(CloseCode::INVALID_FRAME_PAYLOAD, 'text frame is not valid UTF-8');
        }

        return $frame;
    }
}