<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Dto;

/**
 * Email notification DTO with email-specific fields.
 */
final class EmailNotificationDto extends NotificationDto
{
  public function __construct(
    string|array $to,
    ?string $from = null,
    ?string $subject = null,
    ?string $body = null,
    public ?string $html = null,
    public ?string $text = null,
    public ?array $attachments = null,
    public ?array $cc = null,
    public ?array $bcc = null,
    public ?array $replyTo = null,
    public ?array $metadata = null,
  ) {
    parent::__construct($to, $from, $subject, $body, $metadata);

    if ($this->html === null && $this->text === null && $this->body !== null) {
      $this->text = $this->body;
    }
  }
}
