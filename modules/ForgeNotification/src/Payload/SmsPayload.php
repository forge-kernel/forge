<?php

declare(strict_types=1);

namespace Modules\ForgeNotification\Payload;

/**
 * SMS notification payload.
 * Used with sendNotification() to send SMS messages.
 */
final class SmsPayload
{
    public function __construct(
        public readonly string|array $to,
        public readonly string $message,
        public readonly ?string $from = null,
        public readonly ?string $via = null,
    ) {
    }
}
