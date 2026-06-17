<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use App\Modules\ForgeAuth\Exceptions\JwtTokenExpiredException;
use App\Modules\ForgeAuth\Exceptions\JwtTokenInvalidException;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class JwtService
{
  private const string HEADER = '{"typ":"JWT","alg":"HS256"}';
  private ?string $secret = null;

  public function __construct(
    private readonly Config $config
  ) {
  }

  public function encode(array $payload): string
  {
    $secret = $this->getSecret();
    $header = self::HEADER;
    $headerEncoded = $this->base64UrlEncode($header);
    $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);
    $signatureEncoded = $this->base64UrlEncode($signature);

    return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
  }

  public function decode(string $token): array
  {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
      throw new JwtTokenInvalidException('Invalid token format: expected 3 parts');
    }

    [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

    try {
      $secret = $this->getSecret();
    } catch (JwtTokenInvalidException $e) {
      throw $e;
    }

    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);

    try {
      $expectedSignature = $this->base64UrlDecode($signatureEncoded);
    } catch (JwtTokenInvalidException $e) {
      throw new JwtTokenInvalidException('Invalid token signature encoding');
    }

    if (!hash_equals($signature, $expectedSignature)) {
      throw new JwtTokenInvalidException('Invalid token signature');
    }

    try {
      $payloadJson = $this->base64UrlDecode($payloadEncoded);
    } catch (JwtTokenInvalidException $e) {
      throw new JwtTokenInvalidException('Invalid token payload encoding');
    }

    $payload = json_decode($payloadJson, true);

    if (!is_array($payload)) {
      throw new JwtTokenInvalidException('Invalid token payload format');
    }

    if (isset($payload['exp']) && $payload['exp'] < time()) {
      throw new JwtTokenExpiredException();
    }

    return $payload;
  }

  private function getSecret(): string
  {
    if ($this->secret === null) {
      $secret = $this->config->get('forge_auth.jwt.secret');
      if (empty($secret)) {
        throw new JwtTokenInvalidException('JWT secret not configured');
      }
      $this->secret = $secret;
    }

    return $this->secret;
  }

  private function base64UrlEncode(string $data): string
  {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
  }

  private function base64UrlDecode(string $data): string
  {
    $data = str_replace(['-', '_'], ['+', '/'], $data);
    $remainder = strlen($data) % 4;
    if ($remainder) {
      $data .= str_repeat('=', 4 - $remainder);
    }
    $decoded = base64_decode($data, true);
    if ($decoded === false) {
      throw new JwtTokenInvalidException();
    }
    return $decoded;
  }
}

