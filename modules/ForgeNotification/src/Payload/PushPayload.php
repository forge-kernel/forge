<?php

declare(strict_types=1);

namespace Modules\ForgeNotification\Payload;

/**
 * Push notification payload.
 * Used with sendNotification() to send push notifications.
 */
final class PushPayload
{
    public function __construct(
        public readonly string|array $to,
        public readonly ?string $title = null,
        public readonly ?string $body = null,
        public readonly ?array $data = null,
        public readonly ?string $via = null,
    ) {
    }
}
