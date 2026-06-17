<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Providers;

use App\Modules\ForgeDeployment\Contracts\ProviderInterface;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class DigitalOceanProvider implements ProviderInterface
{
  private const API_BASE_URL = 'https://api.digitalocean.com/v2';
  private const POLL_INTERVAL = 5;
  private const MAX_WAIT_TIME = 600;

  public function __construct(
    private readonly string $apiToken
  ) {
  }

  public function createServer(array $config, ?string $sshPublicKey = null): string
  {
    $sshKeys = [];
    if ($sshPublicKey !== null) {
      $existingKeys = $this->listSshKeys();
      $keyId = $this->findOrCreateSshKey($sshPublicKey, $existingKeys);
      if ($keyId !== null) {
        $sshKeys[] = $keyId;
      }
    }

    $payload = [
      'name' => $config['name'] ?? 'forge-server',
      'region' => $config['region'],
      'size' => $config['size'],
      'image' => $config['image'],
    ];

    if (!empty($sshKeys)) {
      $payload['ssh_keys'] = $sshKeys;
    }

    $response = $this->makeRequest('POST', '/droplets', $payload);
    if (!isset($response['droplet']['id'])) {
      throw new \RuntimeException('Failed to create server: ' . ($response['message'] ?? 'Unknown error'));
    }

    return (string) $response['droplet']['id'];
  }

  public function waitForServer(string $serverId): array
  {
    $startTime = time();
    $status = 'new';

    while ($status !== 'active') {
      if (time() - $startTime > self::MAX_WAIT_TIME) {
        throw new \RuntimeException('Server creation timeout');
      }

      sleep(self::POLL_INTERVAL);
      $status = $this->getServerStatus($serverId);
    }

    return $this->getServerInfo($serverId);
  }

  public function getServerStatus(string $serverId): string
  {
    $response = $this->makeRequest('GET', "/droplets/{$serverId}");
    if (!isset($response['droplet']['status'])) {
      throw new \RuntimeException('Failed to get server status');
    }

    return $response['droplet']['status'];
  }

  public function listRegions(): array
  {
    $response = $this->makeRequest('GET', '/regions');
    if (!isset($response['regions'])) {
      return [];
    }

    $regions = [];
    foreach ($response['regions'] as $region) {
      if ($region['available']) {
        $regions[] = [
          'slug' => $region['slug'],
          'name' => $region['name'],
        ];
      }
    }

    return $regions;
  }

  public function listSizes(): array
  {
    $response = $this->makeRequest('GET', '/sizes');
    if (!isset($response['sizes'])) {
      return [];
    }

    $sizes = [];
    foreach ($response['sizes'] as $size) {
      if ($size['available']) {
        $sizes[] = [
          'slug' => $size['slug'],
          'memory' => $size['memory'],
          'vcpus' => $size['vcpus'],
          'disk' => $size['disk'],
          'price_monthly' => $size['price_monthly'],
        ];
      }
    }

    usort($sizes, fn($a, $b) => $a['price_monthly'] <=> $b['price_monthly']);

    return $sizes;
  }

  public function listImages(): array
  {
    $response = $this->makeRequest('GET', '/images?type=distribution');
    if (!isset($response['images'])) {
      return [];
    }

    $images = [];
    foreach ($response['images'] as $image) {
      if ($image['public'] && in_array($image['distribution'], ['Ubuntu', 'Debian'], true)) {
        $images[] = [
          'slug' => $image['slug'],
          'name' => $image['name'],
          'distribution' => $image['distribution'],
        ];
      }
    }

    return $images;
  }

  public function deleteServer(string $serverId): bool
  {
    $response = $this->makeRequest('DELETE', "/droplets/{$serverId}");
    return !isset($response['id']) || $response['id'] === 'not_found';
  }

  private function getServerInfo(string $serverId): array
  {
    $response = $this->makeRequest('GET', "/droplets/{$serverId}");
    if (!isset($response['droplet'])) {
      throw new \RuntimeException('Failed to get server info');
    }

    $droplet = $response['droplet'];
    $ipv4 = null;

    foreach ($droplet['networks']['v4'] ?? [] as $network) {
      if ($network['type'] === 'public') {
        $ipv4 = $network['ip_address'];
        break;
      }
    }

    return [
      'id' => (string) $droplet['id'],
      'name' => $droplet['name'],
      'status' => $droplet['status'],
      'ipv4' => $ipv4,
      'region' => $droplet['region']['slug'],
      'size' => $droplet['size_slug'],
    ];
  }

  private function listSshKeys(): array
  {
    $response = $this->makeRequest('GET', '/account/keys');
    return $response['ssh_keys'] ?? [];
  }

  private function findOrCreateSshKey(string $publicKey, array $existingKeys): ?int
  {
    $keyFingerprint = $this->getKeyFingerprint($publicKey);

    foreach ($existingKeys as $key) {
      if ($key['public_key'] === $publicKey || $key['fingerprint'] === $keyFingerprint) {
        return $key['id'];
      }
    }

    $response = $this->makeRequest('POST', '/account/keys', [
      'name' => 'forge-deployment-' . time(),
      'public_key' => $publicKey,
    ]);

    return $response['ssh_key']['id'] ?? null;
  }

  private function getKeyFingerprint(string $publicKey): string
  {
    $keyParts = explode(' ', $publicKey);
    if (count($keyParts) < 2) {
      return '';
    }

    $keyData = base64_decode($keyParts[1], true);
    if ($keyData === false) {
      return '';
    }

    $hash = md5($keyData);
    return implode(':', str_split($hash, 2));
  }

  private function makeRequest(string $method, string $endpoint, array $data = []): array
  {
    $url = self::API_BASE_URL . $endpoint;
    $headers = [
      'Authorization: Bearer ' . $this->apiToken,
      'Content-Type: application/json',
    ];

    $options = [
      'http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'timeout' => 30,
        'ignore_errors' => true,
      ],
    ];

    if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
      $options['http']['content'] = json_encode($data);
    }

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
      throw new \RuntimeException('API request failed');
    }

    $statusCode = $this->getHttpStatusCode($http_response_header ?? []);
    $decoded = json_decode($response, true);

    if ($statusCode >= 400) {
      $message = $decoded['message'] ?? 'API request failed';
      throw new \RuntimeException("API error ({$statusCode}): {$message}");
    }

    return $decoded ?? [];
  }

  private function getHttpStatusCode(array $headers): int
  {
    if (empty($headers)) {
      return 0;
    }

    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $matches);
    return isset($matches[1]) ? (int) $matches[1] : 0;
  }
}
