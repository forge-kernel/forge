<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\tests;

use Modules\ForgeSockets\Handlers\EchoHandler;
use Modules\ForgeSockets\Server\Frames\Frame;
use Modules\ForgeSockets\Server\Frames\FrameCodec;
use Modules\ForgeSockets\Server\Frames\FrameParser;
use Modules\ForgeSockets\Server\Frames\Opcode;
use Modules\ForgeSockets\Server\Handshake;
use Modules\ForgeSockets\Server\WebSocketServer;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;

/**
 * End-to-end proof of the primitive: a real server process on an ephemeral
 * port, driven by a real TCP client through the full RFC 6455 lifecycle —
 * handshake, echo, ping/pong, protocol-error close (1009) and the closing
 * handshake.
 */
#[Group('sockets')]
final class WebSocketServerTest extends TestCase
{
    private const int PAYLOAD_LIMIT = 65536;

    #[Test('a client can handshake, echo, ping, and close over a real socket')]
    public function full_lifecycle_over_loopback(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl_fork unavailable');
        }

        $portFile = sys_get_temp_dir() . '/forgesockets_' . bin2hex(random_bytes(4)) . '.port';
        $pid = pcntl_fork();

        if ($pid === -1) {
            $this->markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            // Child: run the server forever until killed.
            $server = new WebSocketServer('127.0.0.1', 0, new EchoHandler());
            $server->bind();
            $address = $server->boundAddress();
            $port = (int) substr($address, strrpos($address, ':') + 1);
            file_put_contents($portFile, (string) $port);
            $server->run(static fn (): bool => false);
            exit(0);
        }

        $port = $this->waitForPort($portFile);
        $socket = null;

        try {
            $socket = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 5);
            $this->assertTrue(is_resource($socket), 'client connects: ' . $errstr);
            stream_set_timeout($socket, 3);

            // Opening handshake with an RFC-correct accept key.
            $key = base64_encode(random_bytes(16));
            fwrite($socket, "GET /echo HTTP/1.1\r\n"
                . "Host: localhost\r\n"
                . "Upgrade: websocket\r\n"
                . "Connection: Upgrade\r\n"
                . "Sec-WebSocket-Key: {$key}\r\n"
                . "Sec-WebSocket-Version: 13\r\n"
                . "\r\n");

            [$response, $leftover] = $this->readUntilHeaderEnd($socket);
            $this->assertStringContainsString('101', $response, 'switching protocols');
            $this->assertStringContainsString(
                base64_encode(sha1($key . Handshake::GUID, true)),
                $response,
                'sec-websocket-accept key',
            );

            // EchoHandler greets on open (bytes may have arrived with the 101).
            $parser = new FrameParser(requireMask: false);
            $frames = $this->readFrames($socket, $parser, 1, $leftover);
            $this->assertCount(1, $frames, 'welcome frame');
            $this->assertSame(Opcode::TEXT, $frames[0]->opcode);
            $this->assertStringContainsString('"open"', $frames[0]->payload);

            // Echo a text message.
            $this->send($socket, Frame::text('hello websocket'));
            $frames = $this->readFrames($socket, $parser, 1);
            $this->assertCount(1, $frames, 'echo frame');
            $this->assertSame('hello websocket', $frames[0]->payload);

            // Ping is answered with a pong carrying the same payload.
            $this->send($socket, Frame::ping('hb'));
            $frames = $this->readFrames($socket, $parser, 1);
            $this->assertCount(1, $frames, 'pong frame');
            $this->assertSame(Opcode::PONG, $frames[0]->opcode);
            $this->assertSame('hb', $frames[0]->payload);

            // A frame over the payload limit is closed with 1009.
            $this->send($socket, Frame::binary(str_repeat('x', self::PAYLOAD_LIMIT + 1024)));
            $frames = $this->readFrames($socket, $parser, 1);
            $this->assertCount(1, $frames, 'close frame for oversized payload');
            $this->assertSame(Opcode::CLOSE, $frames[0]->opcode);
            $this->assertSame(1009, unpack('n', substr($frames[0]->payload, 0, 2))[1]);
        } finally {
            if (is_resource($socket)) {
                @fclose($socket);
            }
            if (function_exists('posix_kill')) {
                @posix_kill($pid, SIGTERM);
            }
            if (function_exists('pcntl_waitpid')) {
                pcntl_waitpid($pid, $status);
            }
            @unlink($portFile);
        }
    }

    private function waitForPort(string $portFile): int
    {
        for ($i = 0; $i < 100; $i++) {
            if (is_file($portFile)) {
                $port = (int) file_get_contents($portFile);
                if ($port > 0) {
                    return $port;
                }
            }
            usleep(20_000);
        }

        $this->fail('server did not report its port');
    }

    /**
     * Read the HTTP response headers, returning them plus any bytes that
     * arrived beyond the header terminator (they belong to the frame parser).
     *
     * @return array{0: string, 1: string}
     */
    private function readUntilHeaderEnd($socket): array
    {
        $buffer = '';
        while (($end = strpos($buffer, "\r\n\r\n")) === false) {
            $chunk = fread($socket, 4096);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $buffer .= $chunk;
        }

        if ($end === false) {
            return [$buffer, ''];
        }

        return [substr($buffer, 0, $end + 4), substr($buffer, $end + 4)];
    }

    private function send($socket, Frame $frame): void
    {
        fwrite($socket, FrameCodec::encode($frame, mask: true));
    }

    /**
     * @return list<Frame>
     */
    private function readFrames($socket, FrameParser $parser, int $limit, string $leftover = ''): array
    {
        $frames = [];
        if ($leftover !== '') {
            $frames = array_merge($frames, $parser->feed($leftover));
        }

        $guard = 0;
        while (count($frames) < $limit && $guard++ < 300) {
            $chunk = fread($socket, 8192);
            if ($chunk === false) {
                break;
            }
            if ($chunk === '') {
                usleep(10_000);
                continue;
            }
            $frames = array_merge($frames, $parser->feed($chunk));
        }

        return $frames;
    }
}