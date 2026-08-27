<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Server\Frames;

/**
 * RFC 6455 frame encoding + the stateless unmask helper.
 *
 * Encoding produces a wire frame (optionally client-masked, as a browser
 * client must be). Decoding is the job of {@see FrameParser} (incremental,
 * fragmentation-aware, server-side).
 */
final class FrameCodec
{
    /**
     * Serialize a frame to its wire representation.
     */
    public static function encode(Frame $frame, bool $mask = false): string
    {
        $b0 = ($frame->fin ? 0x80 : 0x00) | $frame->opcode->value;
        $length = strlen($frame->payload);

        $out = chr($b0);
        if ($length < 126) {
            $out .= chr(($mask ? 0x80 : 0x00) | $length);
        } elseif ($length <= 0xFFFF) {
            $out .= chr(($mask ? 0x80 : 0x00) | 126) . pack('n', $length);
        } else {
            $out .= chr(($mask ? 0x80 : 0x00) | 127) . pack('J', $length);
        }

        if ($mask) {
            $key = random_bytes(4);
            $out .= $key . self::unmask($frame->payload, $key);
        } else {
            $out .= $frame->payload;
        }

        return $out;
    }

    /**
     * RFC 6455 §5.3: XOR the payload with a 4-byte masking key.
     */
    public static function unmask(string $payload, string $key): string
    {
        $length = strlen($payload);
        if ($length === 0) {
            return '';
        }

        $keyLen = strlen($key);
        $masked = '';
        for ($i = 0; $i < $length; $i++) {
            $masked .= $payload[$i] ^ $key[$i % $keyLen];
        }

        return $masked;
    }
}