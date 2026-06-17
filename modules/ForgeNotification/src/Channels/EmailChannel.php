<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Channels;

use App\Modules\ForgeEvents\Exceptions\EventException;
use App\Modules\ForgeEvents\Services\EventDispatcher;
use App\Modules\ForgeNotification\Contracts\ChannelInterface;
use App\Modules\ForgeNotification\Contracts\ProviderInterface;
use App\Modules\ForgeNotification\Dto\EmailNotificationDto;
use App\Modules\ForgeNotification\Dto\NotificationDto;
use App\Modules\ForgeNotification\Events\EmailNotificationEvent;
use App\Modules\ForgeNotification\Services\ProviderResolver;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;

/**
 * Email notification channel.
 * Routes email notifications to configured email providers (SMTP, SendGrid, Mailgun, etc.).
 */
#[Service]
final class EmailChannel implements ChannelInterface
{
  private ?string $selectedProvider = null;

  public function __construct(
    private readonly ProviderResolver $providerResolver,
    private readonly Config $config,
    private readonly EventDispatcher $eventDispatcher
  ) {
  }

  /**
   * Set the recipient email address(es).
   *
   * @param string|array $to Email address(es)
   * @return $this
   */
  public function to(string|array $to): self
  {
    $this->to = $to;
    return $this;
  }

  /**
   * Set the sender email address.
   *
   * @param string $from Email address
   * @return $this
   */
  public function from(string $from): self
  {
    $this->from = $from;
    return $this;
  }

  /**
   * Set the email subject.
   *
   * @param string $subject Subject line
   * @return $this
   */
  public function subject(string $subject): self
  {
    $this->subject = $subject;
    return $this;
  }

  /**
   * Set the email body (plain text).
   *
   * @param string $body Email body
   * @return $this
   */
  public function body(string $body): self
  {
    $this->body = $body;
    return $this;
  }

  /**
   * Set the HTML email content.
   *
   * @param string $html HTML content
   * @return $this
   */
  public function html(string $html): self
  {
    $this->html = $html;
    return $this;
  }

  /**
   * Set the plain text email content.
   *
   * @param string $text Plain text content
   * @return $this
   */
  public function text(string $text): self
  {
    $this->text = $text;
    return $this;
  }

  /**
   * Set email attachments.
   *
   * @param array $attachments Array of attachment paths or data
   * @return $this
   */
  public function attachments(array $attachments): self
  {
    $this->attachments = $attachments;
    return $this;
  }

  /**
   * Set CC recipients.
   *
   * @param array $cc Email addresses
   * @return $this
   */
  public function cc(array $cc): self
  {
    $this->cc = $cc;
    return $this;
  }

  /**
   * Set BCC recipients.
   *
   * @param array $bcc Email addresses
   * @return $this
   */
  public function bcc(array $bcc): self
  {
    $this->bcc = $bcc;
    return $this;
  }

  /**
   * Set reply-to address.
   *
   * @param string|array $replyTo Email address(es)
   * @return $this
   */
  public function replyTo(string|array $replyTo): self
  {
    $this->replyTo = is_array($replyTo) ? $replyTo : [$replyTo];
    return $this;
  }

  /**
   * Send a notification through this channel.
   * If $notification is null, builds notification from fluent builder properties.
   *
   * @param NotificationDto|null $notification The notification data (null for fluent API)
   * @param string|null $provider Optional provider name to use (overrides default)
   * @return bool True if sent successfully, false otherwise
   */
  public function send(?NotificationDto $notification = null, ?string $provider = null): bool
  {
    // If no notification provided, build from fluent properties
    if ($notification === null) {
      if (!isset($this->to)) {
        throw new \RuntimeException('Email recipient (to) is required');
      }

      $notification = new EmailNotificationDto(
        to: $this->to,
        from: $this->from ?? null,
        subject: $this->subject ?? null,
        body: $this->body ?? null,
        html: $this->html ?? null,
        text: $this->text ?? null,
        attachments: $this->attachments ?? null,
        cc: $this->cc ?? null,
        bcc: $this->bcc ?? null,
        replyTo: $this->replyTo ?? null,
        metadata: $this->metadata ?? null
      );
    }

    if (!$notification instanceof EmailNotificationDto) {
      throw new \InvalidArgumentException('EmailChannel requires EmailNotificationDto');
    }

    return $this->sendNotification($notification, $provider);
  }

  /**
   * Internal method to send notification via provider.
   */
  private function sendNotification(EmailNotificationDto $notification, ?string $provider = null): bool
  {
    $providerName = $provider ?? $this->selectedProvider ?? $this->getDefaultProvider();
    $provider = $this->providerResolver->resolve($this->getName(), $providerName);

    return $provider->send($notification);
  }

  /**
   * Queue the email notification for async sending.
   *
   * @return void
   * @throws EventException
   */
  public function queue(): void
  {
    if (!isset($this->to)) {
      throw new \RuntimeException('Email recipient (to) is required');
    }

    $notification = new EmailNotificationDto(
      to: $this->to,
      from: $this->from ?? null,
      subject: $this->subject ?? null,
      body: $this->body ?? null,
      html: $this->html ?? null,
      text: $this->text ?? null,
      attachments: $this->attachments ?? null,
      cc: $this->cc ?? null,
      bcc: $this->bcc ?? null,
      replyTo: $this->replyTo ?? null,
      metadata: $this->metadata ?? null
    );

    $this->eventDispatcher->dispatch(
      new EmailNotificationEvent($notification, $this->selectedProvider)
    );
  }

  /**
   * Set the provider to use for this channel.
   *
   * @param string $provider Provider name
   * @return $this
   */
  public function via(string $provider): self
  {
    $this->selectedProvider = $provider;
    return $this;
  }

  /**
   * Get the channel name.
   *
   * @return string
   */
  public function getName(): string
  {
    return 'email';
  }

  /**
   * Get the default provider for this channel.
   *
   * @return string
   */
  public function getDefaultProvider(): string
  {
    return $this->providerResolver->getDefaultProvider($this->getName());
  }

  // Fluent builder properties
  private string|array|null $to = null;
  private ?string $from = null;
  private ?string $subject = null;
  private ?string $body = null;
  private ?string $html = null;
  private ?string $text = null;
  private ?array $attachments = null;
  private ?array $cc = null;
  private ?array $bcc = null;
  private ?array $replyTo = null;
  private ?array $metadata = null;
}
