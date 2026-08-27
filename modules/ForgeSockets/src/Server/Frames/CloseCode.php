<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Server\Frames;

/**
 * RFC 6455 §7.4 close codes. 1006 and 1015 are never sent on the wire — they
 * exist to report abnormal local teardowns to the handler.
 */
enum CloseCode: int
{
    case NORMAL = 1000;
    case GOING_AWAY = 1001;
    case PROTOCOL_ERROR = 1002;
    case UNSUPPORTED_DATA = 1003;
    case ABNORMAL = 1006;
    case INVALID_FRAME_PAYLOAD = 1007;
    case POLICY_VIOLATION = 1008;
    case MESSAGE_TOO_BIG = 1009;
    case MANDATORY_EXTENSION = 1010;
    case INTERNAL_ERROR = 1011;
}