<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\tests;

use Modules\ForgeSockets\Server\Handshake;
use Modules\ForgeSockets\Server\HandshakeResult;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;

#[Group('sockets')]
final class HandshakeTest extends TestCase
{
    private function handshake(): Handshake
    {
        return new Handshake();
    }

    #[Test('a valid RFC 6455 request produces the 101 response with the correct accept key')]
    public function accepts_valid_request(): void
    {
        // The RFC 6455 example handshake.
        $request = "GET /chat?room=1 HTTP/1.1\r\n"
            . "Host: server.example.com\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: keep-alive, Upgrade\r\n"
            . "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "Cookie: session=abc123\r\n"
            . "\r\n";

        $result = $this->handshake()->evaluate($request);

        $this->assertInstanceOf(HandshakeResult::class, $result);
        $this->assertTrue($result->accepted, 'request is accepted');
        $this->assertStringContainsString('HTTP/1.1 101', $result->response);
        $this->assertStringContainsString('Upgrade: websocket', $result->response);
        $this->assertStringContainsString('Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=', $result->response);
    }

    #[Test('an incomplete request returns null until more bytes arrive')]
    public function incomplete_request_waits(): void
    {
        $result = $this->handshake()->evaluate("GET /chat HTTP/1.1\r\nUpgrade: websocke");

        $this->assertNull($result, 'needs more bytes');
    }

    #[Test('a request without the upgrade headers is rejected')]
    public function rejects_missing_upgrade(): void
    {
        $request = "GET /chat HTTP/1.1\r\nHost: x\r\nSec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\nSec-WebSocket-Version: 13\r\n\r\n";

        $result = $this->handshake()->evaluate($request);

        $this->assertNotNull($result);
        $this->assertFalse($result->accepted);
    }

    #[Test('an unsupported websocket version is rejected')]
    public function rejects_wrong_version(): void
    {
        $request = "GET /chat HTTP/1.1\r\nHost: x\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\nSec-WebSocket-Version: 12\r\n\r\n";

        $result = $this->handshake()->evaluate($request);

        $this->assertNotNull($result);
        $this->assertFalse($result->accepted);
    }

    #[Test('a malformed sec-websocket-key is rejected')]
    public function rejects_bad_key(): void
    {
        $request = "GET /chat HTTP/1.1\r\nHost: x\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: not-a-valid-key!!\r\nSec-WebSocket-Version: 13\r\n\r\n";

        $result = $this->handshake()->evaluate($request);

        $this->assertNotNull($result);
        $this->assertFalse($result->accepted);
    }

    #[Test('the parsed path and headers are exposed for authentication')]
    public function exposes_path_and_headers(): void
    {
        $request = "GET /ws/arcades/the-hall?game=parchis HTTP/1.1\r\n"
            . "Host: novaspace.local\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "Cookie: nova_session=xyz\r\n"
            . "\r\n";

        $result = $this->handshake()->evaluate($request);

        $this->assertNotNull($result);
        $this->assertTrue($result->accepted);
        $this->assertSame('/ws/arcades/the-hall', $result->path);
        $this->assertSame('game=parchis', $result->query);
        $this->assertSame('nova_session=xyz', $result->headers['cookie'] ?? null);
    }
}