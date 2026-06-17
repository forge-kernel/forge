<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Utils;

use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Session\SessionInterface;
use RuntimeException;

#[Service]
final class UploadSignature
{
  private const SESSION_PREFIX = 'forge_storage:upload:';

  private string $appKey;

  public function __construct(private Config $config)
  {
    $this->appKey = (string) $config->get('security.app_key', '');

    if ($this->appKey === '') {
      $this->appKey = (string) $config->get('app.key', '');
    }

    if ($this->appKey === '') {
      $this->appKey = (string) env('APP_KEY', '');
    }

    if ($this->appKey === '') {
      throw new RuntimeException('App key required for upload signatures. Please set APP_KEY in your .env file or config/security.php as "app_key".');
    }
  }

  private function canonicalize(mixed $v): mixed
  {
    if (is_array($v)) {
      $isAssoc = array_keys($v) !== range(0, count($v) - 1);
      if ($isAssoc) {
        ksort($v);
      }
      foreach ($v as $k => $val) {
        $v[$k] = $this->canonicalize($val);
      }
      return $v;
    }
    if (is_object($v)) {
      return $this->canonicalize(get_object_vars($v));
    }
    return $v;
  }

  private function canonicalJson(mixed $v): string
  {
    $v = $this->canonicalize($v);
    return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

  public function generate(string $location, array $config, SessionInterface $session): string
  {
    $sessionId = $session->getId();
    $payload = [
      'location' => $location,
      'allowed_types' => $config['allowed_types'] ?? null,
      'max_size' => $config['max_size'] ?? null,
      'sid' => $sessionId,
    ];

    $json = $this->canonicalJson($payload);
    $signature = hash_hmac('sha256', $json, $this->appKey);

    $sessionKey = self::SESSION_PREFIX . $signature;
    $session->set($sessionKey, [
      'location' => $location,
      'config' => $config,
      'created_at' => time(),
    ]);

    return $signature;
  }

  public function verify(string $signature, SessionInterface $session): array
  {
    $sessionKey = self::SESSION_PREFIX . $signature;
    $stored = $session->get($sessionKey);

    if ($stored === null || !is_array($stored)) {
      throw new RuntimeException('Upload signature not found or invalid. Upload may have been tampered with.');
    }

    return $stored;
  }
}
