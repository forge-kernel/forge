<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Server;

/**
 * RFC 6455 §4 opening handshake.
 *
 * Parses the client's HTTP Upgrade request from the stream buffer and either
 * produces the 101 response (with the Sec-WebSocket-Accept key), rejects it,
 * or asks for more bytes (returns null) when the request headers are
 * incomplete. The request path/query and headers are preserved so an
 * authenticator can resolve the user (cookie → session).
 */
final class Handshake
{
    public const string GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    private const int MAX_REQUEST = 16384;

    /**
     * @return HandshakeResult|null null when more request bytes are needed
     */
    public function evaluate(string $request): ?HandshakeResult
    {
        $end = strpos($request, "\r\n\r\n");
        if ($end === false) {
            if (strlen($request) > self::MAX_REQUEST) {
                return HandshakeResult::rejected();
            }

            return null;
        }

        $lines = explode("\r\n", substr($request, 0, $end));
        $requestLine = array_shift($lines);

        if (!is_string($requestLine) || !preg_match('#^GET (\S+) HTTP/1\.1$#', $requestLine, $match)) {
            return HandshakeResult::rejected();
        }

        $target = $match[1];
        [$path, $query] = array_pad(explode('?', $target, 2), 2, '');

        $headers = [];
        foreach ($lines as $line) {
            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }
            $headers[strtolower(trim(substr($line, 0, $colon)))] = trim(substr($line, $colon + 1));
        }

        $key = $headers['sec-websocket-key'] ?? '';
        $version = $headers['sec-websocket-version'] ?? '';
        $upgrade = strtolower($headers['upgrade'] ?? '');
        $connection = strtolower($headers['connection'] ?? '');

        if ($version !== '13') {
            return HandshakeResult::rejected();
        }

        if ($upgrade !== 'websocket' || !str_contains($connection, 'upgrade')) {
            return HandshakeResult::rejected();
        }

        if (!preg_match('#^[A-Za-z0-9+/=]{22,24}$#', $key)) {
            return HandshakeResult::rejected();
        }

        $accept = base64_encode(sha1($key . self::GUID, true));

        $response = "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: {$accept}\r\n"
            . "\r\n";

        return HandshakeResult::accepted($response, $path, $query, $headers);
    }
}