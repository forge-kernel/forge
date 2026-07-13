<?php

declare(strict_types=1);

namespace Modules\ForgeNotification\Payload;

/**
 * Email notification payload.
 * Used with sendNotification() to send emails.
 */
final class EmailPayload
{
    public function __construct(
        public readonly string|array $to,
        public readonly ?string $subject = null,
        public readonly ?string $html = null,
        public readonly ?string $text = null,
        public readonly ?string $from = null,
        public readonly ?array $cc = null,
        public readonly ?array $bcc = null,
        public readonly string|array|null $replyTo = null,
        public readonly ?array $attachments = null,
        public readonly ?string $via = null,
    ) {
    }
}
