<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Providers\Email;

use App\Modules\ForgeNotification\Contracts\ProviderInterface;
use App\Modules\ForgeNotification\Dto\EmailNotificationDto;
use App\Modules\ForgeNotification\Dto\NotificationDto;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;

/**
 * SMTP email provider.
 * Sends emails using native SMTP socket connections.
 * Supports authentication, TLS/SSL, and works with Mailpit and production SMTP servers.
 */
#[Service]
final class SmtpProvider implements ProviderInterface
{
  private array $config = [];
  private $socket = null;
  private string $lastResponse = '';

  public function __construct(private readonly Config $configService)
  {
    $this->loadConfig();
  }

  /**
   * Load provider configuration from config.
   */
  private function loadConfig(): void
  {
    $this->config = $this->configService->get('forge_notification.channels.email.providers.smtp', [
      'host' => env('SMTP_HOST', 'localhost'),
      'port' => env('SMTP_PORT', 587),
      'username' => env('SMTP_USERNAME', ''),
      'password' => env('SMTP_PASSWORD', ''),
      'encryption' => env('SMTP_ENCRYPTION', 'tls'), // tls, ssl, or none
      'from_address' => env('SMTP_FROM_ADDRESS', 'noreply@example.com'),
      'from_name' => env('SMTP_FROM_NAME', 'Forge Application'),
    ]);
  }

  /**
   * Set provider configuration (called by ProviderResolver if method exists).
   *
   * @param array $config Configuration array
   * @return void
   */
  public function setConfig(array $config): void
  {
    $this->config = array_merge($this->config, $config);
  }

  /**
   * Send a notification using this provider.
   *
   * @param NotificationDto $notification The notification data
   * @return bool True if sent successfully, false otherwise
   */
  public function send(NotificationDto $notification): bool
  {
    if (!$notification instanceof EmailNotificationDto) {
      throw new \InvalidArgumentException('SmtpProvider requires EmailNotificationDto');
    }

    if (!$this->validate()) {
      throw new \RuntimeException('SMTP provider is not properly configured');
    }

    try {
      $this->connect();
      $this->sendEhlo();

      if ($this->config['encryption'] === 'tls') {
        $this->startTls();
        $this->sendEhlo();
      }

      if (!empty($this->config['username']) && !empty($this->config['password'])) {
        $this->authenticate();
      }

      $this->sendMailFrom($notification->from ?? $this->config['from_address']);

      $allRecipients = array_merge(
        $notification->getToArray(),
        $notification->cc ?? [],
        $notification->bcc ?? []
      );

      foreach ($allRecipients as $recipient) {
        $this->sendRcptTo($recipient);
      }

      $this->sendData($notification);
      $this->quit();

      return true;
    } catch (\Exception $e) {
      error_log("SMTP Error: " . $e->getMessage());
      $this->disconnect();
      return false;
    } finally {
      $this->disconnect();
    }
  }

  /**
   * Connect to SMTP server.
   *
   * @throws \RuntimeException If connection fails
   */
  private function connect(): void
  {
    $host = $this->config['host'] ?? 'localhost';
    $port = (int) ($this->config['port'] ?? 587);
    $encryption = $this->config['encryption'] ?? 'none';

    $context = stream_context_create([
      'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
      ],
    ]);

    if ($encryption === 'ssl') {
      $host = 'ssl://' . $host;
    }

    $this->socket = @stream_socket_client(
      "$host:$port",
      $errno,
      $errstr,
      10,
      STREAM_CLIENT_CONNECT,
      $context
    );

    if (!$this->socket) {
      throw new \RuntimeException("Failed to connect to SMTP server: $errstr ($errno)");
    }

    stream_set_blocking($this->socket, true);
    stream_set_timeout($this->socket, 10);

    $this->readResponse();
  }

  /**
   * Disconnect from SMTP server.
   */
  private function disconnect(): void
  {
    if ($this->socket) {
      @fclose($this->socket);
      $this->socket = null;
    }
  }

  /**
   * Send SMTP command and read response.
   *
   * @param string $command The SMTP command to send
   * @return string The server response
   * @throws \RuntimeException If command fails
   */
  private function sendCommand(string $command): string
  {
    if (!$this->socket) {
      throw new \RuntimeException('Not connected to SMTP server');
    }

    fwrite($this->socket, $command . "\r\n");
    return $this->readResponse();
  }

  /**
   * Read SMTP server response.
   *
   * @return string The server response
   * @throws \RuntimeException If response indicates error or timeout
   */
  private function readResponse(): string
  {
    if (!$this->socket) {
      throw new \RuntimeException('Not connected to SMTP server');
    }

    $response = '';
    $maxLines = 10;
    $lineCount = 0;

    while ($lineCount < $maxLines) {
      $lineCount++;

      $meta = stream_get_meta_data($this->socket);
      if ($meta['timed_out']) {
        throw new \RuntimeException('SMTP read timeout');
      }

      if (feof($this->socket)) {
        throw new \RuntimeException('SMTP server closed connection');
      }

      $line = @fgets($this->socket, 515);

      if ($line === false) {
        $meta = stream_get_meta_data($this->socket);
        if ($meta['timed_out']) {
          throw new \RuntimeException('SMTP read timeout');
        }
        if (feof($this->socket)) {
          throw new \RuntimeException('SMTP server closed connection');
        }
        throw new \RuntimeException('Error reading from SMTP server');
      }

      $response .= $line;
      if (strlen($line) >= 4) {
        $char4 = substr($line, 3, 1);
        if ($char4 === ' ') {
          break;
        }
      }
    }

    $this->lastResponse = $response;

    if (empty($response)) {
      throw new \RuntimeException('Empty response from SMTP server');
    }

    $lines = explode("\n", $response);
    $firstLine = trim($lines[0]);
    if (empty($firstLine)) {
      throw new \RuntimeException('Invalid SMTP response format');
    }

    $code = (int) substr($firstLine, 0, 3);

    if ($code >= 400) {
      throw new \RuntimeException("SMTP error: $response");
    }

    return $response;
  }

  /**
   * Send EHLO command.
   */
  private function sendEhlo(): void
  {
    $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $this->sendCommand("EHLO $hostname");
  }

  /**
   * Start TLS encryption.
   *
   * @throws \RuntimeException If TLS fails
   */
  private function startTls(): void
  {
    $this->sendCommand('STARTTLS');

    if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
      throw new \RuntimeException('Failed to enable TLS');
    }
  }

  /**
   * Authenticate with SMTP server.
   *
   * @throws \RuntimeException If authentication fails
   */
  private function authenticate(): void
  {
    $username = $this->config['username'] ?? '';
    $password = $this->config['password'] ?? '';

    if (empty($username) || empty($password)) {
      return;
    }

    try {
      $this->sendCommand('AUTH LOGIN');
      $this->sendCommand(base64_encode($username));
      $this->sendCommand(base64_encode($password));
    } catch (\RuntimeException $e) {
      $authString = base64_encode("\0" . $username . "\0" . $password);
      $this->sendCommand("AUTH PLAIN $authString");
    }
  }

  /**
   * Send MAIL FROM command.
   *
   * @param string $from The sender email address
   */
  private function sendMailFrom(string $from): void
  {
    $this->sendCommand("MAIL FROM:<$from>");
  }

  /**
   * Send RCPT TO command.
   *
   * @param string $to The recipient email address
   */
  private function sendRcptTo(string $to): void
  {
    $this->sendCommand("RCPT TO:<$to>");
  }

  /**
   * Send email data.
   *
   * @param EmailNotificationDto $notification The email notification
   */
  private function sendData(EmailNotificationDto $notification): void
  {
    $response = $this->sendCommand('DATA');

    $code = (int) substr(trim($response), 0, 3);
    if ($code !== 354) {
      throw new \RuntimeException("SMTP server not ready for data: $response");
    }

    $emailBody = $this->buildEmailContent($notification);
    $bytesWritten = fwrite($this->socket, $emailBody);

    if ($bytesWritten === false) {
      throw new \RuntimeException('Failed to write email data to SMTP server');
    }

    fflush($this->socket);

    $response = $this->readResponse();
    $code = (int) substr(trim($response), 0, 3);
    if ($code !== 250) {
      throw new \RuntimeException("SMTP server rejected email data: $response");
    }
  }

  /**
   * Send QUIT command.
   */
  private function quit(): void
  {
    try {
      $this->sendCommand('QUIT');
    } catch (\RuntimeException $e) {
    }
  }

  /**
   * Build complete email content with headers and body.
   *
   * @param EmailNotificationDto $notification
   * @return string
   */
  private function buildEmailContent(EmailNotificationDto $notification): string
  {
    $headers = [];
    $boundary = '----=_Part_' . md5(uniqid((string) time()));

    $from = $notification->from ?? $this->config['from_address'];
    $fromName = $this->config['from_name'] ?? '';
    if ($fromName) {
      $headers[] = "From: {$fromName} <{$from}>";
    } else {
      $headers[] = "From: <{$from}>";
    }

    $to = $notification->getToArray();
    if (!empty($to)) {
      $headers[] = "To: " . implode(', ', $to);
    }

    if ($notification->replyTo !== null && !empty($notification->replyTo)) {
      $replyTo = is_array($notification->replyTo) ? implode(', ', $notification->replyTo) : $notification->replyTo;
      $headers[] = "Reply-To: {$replyTo}";
    }

    if ($notification->cc !== null && !empty($notification->cc)) {
      $cc = is_array($notification->cc) ? $notification->cc : [$notification->cc];
      $headers[] = "Cc: " . implode(', ', $cc);
    }

    $headers[] = "Subject: " . ($notification->subject ?? '');

    $headers[] = "Date: " . date('r');

    $headers[] = "Message-ID: <" . md5(uniqid((string) time())) . "@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">";

    $headers[] = "MIME-Version: 1.0";

    $hasHtml = $notification->html !== null;
    $hasText = $notification->text !== null || $notification->body !== null;

    if ($hasHtml && $hasText) {
      $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
      $body = "\r\n--{$boundary}\r\n";
      $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
      $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
      $body .= $notification->text ?? $notification->body ?? '';
      $body .= "\r\n--{$boundary}\r\n";
      $body .= "Content-Type: text/html; charset=UTF-8\r\n";
      $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
      $body .= $notification->html;
      $body .= "\r\n--{$boundary}--\r\n";
    } elseif ($hasHtml) {
      $headers[] = "Content-Type: text/html; charset=UTF-8";
      $body = $notification->html;
    } else {
      $headers[] = "Content-Type: text/plain; charset=UTF-8";
      $body = $notification->text ?? $notification->body ?? '';
    }

    return implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n";
  }

  /**
   * Get the provider name.
   *
   * @return string
   */
  public function getName(): string
  {
    return 'smtp';
  }

  /**
   * Validate that the provider is properly configured.
   *
   * @return bool True if valid, false otherwise
   */
  public function validate(): bool
  {
    $fromAddress = $this->config['from_address'] ?? null;
    return !empty($fromAddress) && filter_var($fromAddress, FILTER_VALIDATE_EMAIL) !== false;
  }
}
