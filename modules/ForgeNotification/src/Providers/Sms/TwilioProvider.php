<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Providers\Sms;

use App\Modules\ForgeNotification\Contracts\ProviderInterface;
use App\Modules\ForgeNotification\Dto\NotificationDto;
use App\Modules\ForgeNotification\Dto\SmsNotificationDto;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;

/**
 * Twilio SMS provider.
 * Sends SMS messages using Twilio's API.
 */
#[Service]
final class TwilioProvider implements ProviderInterface
{
  private array $config = [];
  private ?string $accountSid = null;
  private ?string $authToken = null;
  private ?string $fromNumber = null;

  public function __construct(private readonly Config $configService)
  {
    $this->loadConfig();
  }

  /**
   * Load provider configuration from config.
   */
  private function loadConfig(): void
  {
    $this->config = $this->configService->get('forge_notification.channels.sms.providers.twilio', [
      'account_sid' => env('TWILIO_ACCOUNT_SID', ''),
      'auth_token' => env('TWILIO_AUTH_TOKEN', ''),
      'from' => env('TWILIO_FROM', ''),
    ]);

    $this->accountSid = $this->config['account_sid'] ?? null;
    $this->authToken = $this->config['auth_token'] ?? null;
    $this->fromNumber = $this->config['from'] ?? null;
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
    $this->accountSid = $this->config['account_sid'] ?? $this->accountSid;
    $this->authToken = $this->config['auth_token'] ?? $this->authToken;
    $this->fromNumber = $this->config['from'] ?? $this->fromNumber;
  }

  /**
   * Send a notification using this provider.
   *
   * @param NotificationDto $notification The notification data
   * @return bool True if sent successfully, false otherwise
   */
  public function send(NotificationDto $notification): bool
  {
    if (!$notification instanceof SmsNotificationDto) {
      throw new \InvalidArgumentException('TwilioProvider requires SmsNotificationDto');
    }

    if (!$this->validate()) {
      throw new \RuntimeException('Twilio provider is not properly configured');
    }

    $toNumbers = $notification->getToArray();
    $fromNumber = $notification->from ?? $this->fromNumber;
    $message = $notification->message;

    if (empty($fromNumber)) {
      throw new \RuntimeException('Twilio "from" number is required');
    }

    $success = true;

    // Send to each recipient
    foreach ($toNumbers as $toNumber) {
      if (!$this->sendSms($toNumber, $fromNumber, $message)) {
        $success = false;
      }
    }

    return $success;
  }

  /**
   * Send SMS via Twilio API.
   *
   * @param string $to Recipient phone number
   * @param string $from Sender phone number
   * @param string $message Message text
   * @return bool True if sent successfully
   */
  private function sendSms(string $to, string $from, string $message): bool
  {
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";

    $data = [
      'From' => $from,
      'To' => $to,
      'Body' => $message,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$this->accountSid}:{$this->authToken}");
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/x-www-form-urlencoded',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
      error_log("Twilio SMS error: {$error}");
      return false;
    }

    if ($httpCode !== 200 && $httpCode !== 201) {
      error_log("Twilio SMS failed with HTTP {$httpCode}: {$response}");
      return false;
    }

    $result = json_decode($response, true);
    if (isset($result['status']) && in_array($result['status'], ['queued', 'sending', 'sent'], true)) {
      return true;
    }

    error_log("Twilio SMS failed: {$response}");
    return false;
  }

  /**
   * Get the provider name.
   *
   * @return string
   */
  public function getName(): string
  {
    return 'twilio';
  }

  /**
   * Validate that the provider is properly configured.
   *
   * @return bool True if valid, false otherwise
   */
  public function validate(): bool
  {
    return !empty($this->accountSid)
      && !empty($this->authToken)
      && !empty($this->fromNumber);
  }
}
