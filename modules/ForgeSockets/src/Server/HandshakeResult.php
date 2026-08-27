<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Server;

/**
 * The verdict of one RFC 6455 opening-handshake evaluation.
 *
 * Accepted carries the 101 response bytes and the parsed request (path/query
 * + headers, exposed for the authenticator); rejected just means the server
 * should refuse the connection.
 */
final readonly class HandshakeResult
{
    private function __construct(
        public bool $accepted,
        public string $response = '',
        public string $path = '',
        public string $query = '',
        public array $headers = [],
    ) {}

    public static function accepted(string $response, string $path, string $query, array $headers): self
    {
        return new self(true, $response, $path, $query, $headers);
    }

    public static function rejected(): self
    {
        return new self(false);
    }
}