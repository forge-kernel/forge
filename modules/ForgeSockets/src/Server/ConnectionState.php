<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Server;

enum ConnectionState: string
{
    case HANDSHAKING = 'handshaking';
    case OPEN = 'open';
    case CLOSING = 'closing';
    case CLOSED = 'closed';
}