<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class SshKeyManager
{
  public function locatePublicKey(?string $customPath = null): ?string
  {
    if ($customPath !== null && $customPath !== '') {
      $expandedPath = $this->expandPath($customPath);
      if ($this->isValidPublicKey($expandedPath)) {
        return $expandedPath;
      }
      return null;
    }

    $defaultPath = $this->expandPath('~/.ssh/id_rsa.pub');
    if ($this->isValidPublicKey($defaultPath)) {
      return $defaultPath;
    }

    return null;
  }

  public function readPublicKey(?string $path = null): ?string
  {
    $keyPath = $this->locatePublicKey($path);
    if ($keyPath === null) {
      return null;
    }

    $content = file_get_contents($keyPath);
    if ($content === false) {
      return null;
    }

    $content = trim($content);
    if (!$this->validateKeyFormat($content)) {
      return null;
    }

    return $content;
  }

  public function isValidPublicKey(string $path): bool
  {
    if (!file_exists($path) || !is_readable($path)) {
      return false;
    }

    $content = file_get_contents($path);
    if ($content === false) {
      return false;
    }

    return $this->validateKeyFormat(trim($content));
  }

  private function validateKeyFormat(string $keyContent): bool
  {
    if (empty($keyContent)) {
      return false;
    }

    $parts = explode(' ', $keyContent);
    if (count($parts) < 2) {
      return false;
    }

    $keyTypes = ['ssh-rsa', 'ssh-ed25519', 'ecdsa-sha2-nistp256', 'ecdsa-sha2-nistp384', 'ecdsa-sha2-nistp521', 'ssh-dss'];
    return in_array($parts[0], $keyTypes, true);
  }

  private function expandPath(string $path): string
  {
    if (str_starts_with($path, '~/')) {
      $home = $_SERVER['HOME'] ?? getenv('HOME') ?? '';
      if ($home !== '') {
        return $home . substr($path, 1);
      }
    }
    return $path;
  }
}
