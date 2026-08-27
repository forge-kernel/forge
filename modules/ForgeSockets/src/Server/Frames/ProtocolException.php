<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Server\Frames;

/**
 * A frame-level violation of RFC 6455. Carries the close code the connection
 * should be torn down with, so the transport can fail a client precisely.
 */
final class ProtocolException extends \RuntimeException
{
    public function __construct(
        private readonly CloseCode $closeCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function closeCode(): CloseCode
    {
        return $this->closeCode;
    }
}