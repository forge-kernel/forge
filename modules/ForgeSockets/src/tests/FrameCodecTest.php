<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\tests;

use Modules\ForgeSockets\Server\Frames\CloseCode;
use Modules\ForgeSockets\Server\Frames\Frame;
use Modules\ForgeSockets\Server\Frames\FrameCodec;
use Modules\ForgeSockets\Server\Frames\FrameParser;
use Modules\ForgeSockets\Server\Frames\Opcode;
use Modules\ForgeSockets\Server\Frames\ProtocolException;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;

#[Group('sockets')]
final class FrameCodecTest extends TestCase
{
    private function parser(int $maxPayload = 65536): FrameParser
    {
        return new FrameParser(maxPayload: $maxPayload);
    }

    #[Test('a masked text frame round-trips through encode and the parser')]
    public function text_round_trip(): void
    {
        $wire = FrameCodec::encode(Frame::text('hello websocket'), mask: true);
        $frames = $this->parser()->feed($wire);

        $this->assertCount(1, $frames, 'one frame parsed');
        $this->assertSame(Opcode::TEXT, $frames[0]->opcode);
        $this->assertTrue($frames[0]->fin, 'final frame');
        $this->assertSame('hello websocket', $frames[0]->payload);
    }

    #[Test('payload length boundary encodings parse correctly')]
    public function length_boundaries(): void
    {
        $cases = ['', str_repeat('b', 125), str_repeat('c', 126), str_repeat('d', 65535), str_repeat('e', 65536)];

        foreach ($cases as $payload) {
            $wire = FrameCodec::encode(Frame::binary($payload), mask: true);
            $frames = $this->parser(maxPayload: 200000)->feed($wire);
            $this->assertCount(1, $frames, 'exactly one frame for ' . strlen($payload) . ' bytes');
            $this->assertSame($payload, $frames[0]->payload);
        }
    }

    #[Test('multiple frames in a single packet are all emitted')]
    public function multiple_frames_per_packet(): void
    {
        $wire = FrameCodec::encode(Frame::text('one'), mask: true)
            . FrameCodec::encode(Frame::text('two'), mask: true)
            . FrameCodec::encode(Frame::binary('three'), mask: true);

        $frames = $this->parser()->feed($wire);

        $this->assertCount(3, $frames);
        $this->assertSame('one', $frames[0]->payload);
        $this->assertSame('two', $frames[1]->payload);
        $this->assertSame('three', $frames[2]->payload);
    }

    #[Test('a frame split across feeds is buffered and completed')]
    public function split_across_packets(): void
    {
        $wire = FrameCodec::encode(Frame::text('split me nicely'), mask: true);
        $parser = $this->parser();

        $mid = intdiv(strlen($wire), 2);
        $this->assertSame([], $parser->feed(substr($wire, 0, $mid)), 'partial frame yields nothing yet');
        $frames = $parser->feed(substr($wire, $mid));

        $this->assertCount(1, $frames);
        $this->assertSame('split me nicely', $frames[0]->payload);
    }

    #[Test('a fragmented message is reassembled into one complete frame')]
    public function reassembles_fragments(): void
    {
        $wire = FrameCodec::encode(new Frame(false, Opcode::TEXT, 'hel'), mask: true)
            . FrameCodec::encode(new Frame(false, Opcode::CONTINUATION, 'lo '), mask: true)
            . FrameCodec::encode(new Frame(true, Opcode::CONTINUATION, 'world'), mask: true);

        $frames = $this->parser()->feed($wire);

        $this->assertCount(1, $frames);
        $this->assertSame(Opcode::TEXT, $frames[0]->opcode);
        $this->assertTrue($frames[0]->fin);
        $this->assertSame('hello world', $frames[0]->payload);
    }

    #[Test('control frames interleaved in a fragmented message are delivered immediately')]
    public function control_frame_interleaved(): void
    {
        $wire = FrameCodec::encode(new Frame(false, Opcode::TEXT, 'ab'), mask: true)
            . FrameCodec::encode(Frame::ping('keepalive'), mask: true)
            . FrameCodec::encode(new Frame(true, Opcode::CONTINUATION, 'cd'), mask: true);

        $frames = $this->parser()->feed($wire);

        $this->assertCount(2, $frames, 'ping plus completed text');
        $this->assertSame(Opcode::PING, $frames[0]->opcode);
        $this->assertSame('keepalive', $frames[0]->payload);
        $this->assertSame(Opcode::TEXT, $frames[1]->opcode);
        $this->assertSame('abcd', $frames[1]->payload);
    }

    #[Test('an unmasked client frame is a protocol error')]
    public function rejects_unmasked_client_frame(): void
    {
        $this->expectProtocol(CloseCode::PROTOCOL_ERROR, function (): void {
            $this->parser()->feed(FrameCodec::encode(Frame::text('nope'), mask: false));
        });
    }

    #[Test('a frame over the max payload is rejected with 1009')]
    public function rejects_oversized_frame(): void
    {
        $this->expectProtocol(CloseCode::MESSAGE_TOO_BIG, function (): void {
            $this->parser(maxPayload: 1024)->feed(FrameCodec::encode(Frame::binary(str_repeat('x', 2048)), mask: true));
        });
    }

    #[Test('invalid UTF-8 in a text frame is rejected with 1007')]
    public function rejects_invalid_utf8(): void
    {
        $this->expectProtocol(CloseCode::INVALID_FRAME_PAYLOAD, function (): void {
            $this->parser()->feed(FrameCodec::encode(Frame::text("\xC3\x28"), mask: true));
        });
    }

    #[Test('reserved opcodes are rejected with 1002')]
    public function rejects_reserved_opcode(): void
    {
        $this->expectProtocol(CloseCode::PROTOCOL_ERROR, function (): void {
            $this->parser()->feed("\x83\x80\x00\x00\x00\x00");
        });
    }

    #[Test('rsv bits set are rejected with 1002')]
    public function rejects_rsv_bits(): void
    {
        // Hand-build a masked text frame with RSV1 (0x40) set and a zero mask key.
        $this->expectProtocol(CloseCode::PROTOCOL_ERROR, function (): void {
            $this->parser()->feed("\xC1\x81\x00\x00\x00\x00x");
        });
    }

    #[Test('a control frame with a payload over 125 bytes is rejected')]
    public function rejects_large_control_frame(): void
    {
        // Hand-build a masked ping with a 126-byte payload length.
        $this->expectProtocol(CloseCode::PROTOCOL_ERROR, function (): void {
            $this->parser()->feed("\x89\xFE\x00\x7E\x00\x00\x00\x00" . str_repeat('p', 126));
        });
    }

    #[Test('close frames carry their code and reason')]
    public function close_frame_codec(): void
    {
        $wire = FrameCodec::encode(Frame::close(1001, 'bye'), mask: true);
        $frames = $this->parser()->feed($wire);

        $this->assertCount(1, $frames);
        $this->assertSame(Opcode::CLOSE, $frames[0]->opcode);
        $this->assertSame(1001, unpack('n', substr($frames[0]->payload, 0, 2))[1]);
        $this->assertSame('bye', substr($frames[0]->payload, 2));
    }

    private function expectProtocol(CloseCode $code, callable $fn): void
    {
        try {
            $fn();
            $this->fail('expected a ProtocolException');
        } catch (ProtocolException $e) {
            $this->assertSame($code, $e->closeCode(), 'close code ' . $code->value);
        }
    }
}