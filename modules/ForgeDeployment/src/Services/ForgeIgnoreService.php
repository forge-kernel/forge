<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class ForgeIgnoreService
{
  private array $patterns = [];
  private string $basePath;

  public function load(string $basePath): void
  {
    $this->basePath = rtrim($basePath, '/');
    $this->patterns = [];

    $ignoreFile = $this->basePath . '/.forgeignore';
    if (!file_exists($ignoreFile)) {
      return;
    }

    $lines = file($ignoreFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
      return;
    }

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || str_starts_with($line, '#')) {
        continue;
      }

      $this->patterns[] = $this->normalizePattern($line);
    }
  }

  public function shouldIgnore(string $path): bool
  {
    if (empty($this->patterns)) {
      return false;
    }

    $relativePath = $this->getRelativePath($path);
    if ($relativePath === null) {
      return false;
    }

    $normalizedPath = str_replace('\\', '/', $relativePath);

    foreach ($this->patterns as $pattern) {
      if ($this->matchesPattern($pattern, $normalizedPath)) {
        return true;
      }
    }

    $pathParts = explode('/', $normalizedPath);
    for ($i = 1; $i <= count($pathParts); $i++) {
      $parentPath = implode('/', array_slice($pathParts, 0, $i));
      foreach ($this->patterns as $pattern) {
        if ($pattern['isDirectory'] && $this->matchesPattern($pattern, $parentPath)) {
          return true;
        }
      }
    }

    return false;
  }

  private function normalizePattern(string $pattern): array
  {
    $pattern = trim($pattern);

    if (str_starts_with($pattern, './')) {
      $pattern = substr($pattern, 2);
    }

    $isNegation = str_starts_with($pattern, '!');
    if ($isNegation) {
      $pattern = substr($pattern, 1);
    }

    $hasWildcardSuffix = str_ends_with($pattern, '/*');
    if ($hasWildcardSuffix) {
      $pattern = rtrim($pattern, '/*');
      $isDirectory = true;
    } else {
      $isDirectory = str_ends_with($pattern, '/');
      if ($isDirectory) {
        $pattern = rtrim($pattern, '/');
      }
    }

    $isRoot = str_starts_with($pattern, '/');
    if ($isRoot) {
      $pattern = substr($pattern, 1);
    }

    return [
      'pattern' => $pattern,
      'isDirectory' => $isDirectory,
      'isRoot' => $isRoot,
      'isNegation' => $isNegation,
    ];
  }

  private function matchesPattern(array $patternInfo, string $path): bool
  {
    $pattern = $patternInfo['pattern'];
    $isDirectory = $patternInfo['isDirectory'];
    $isRoot = $patternInfo['isRoot'];

    $normalizedPattern = str_replace('\\', '/', $pattern);
    $normalizedPath = str_replace('\\', '/', $path);

    if ($isRoot) {
      if (!str_starts_with($normalizedPath, $normalizedPattern)) {
        return false;
      }
      $remaining = substr($normalizedPath, strlen($normalizedPattern));
      if ($isDirectory) {
        return $remaining === '' || str_starts_with($remaining, '/');
      }
      return $remaining === '' || str_starts_with($remaining, '/');
    }

    if ($isDirectory) {
      if (str_starts_with($normalizedPath, $normalizedPattern . '/')) {
        return true;
      }
      if ($normalizedPath === $normalizedPattern) {
        return true;
      }
      $regex = $this->patternToRegex($normalizedPattern);
      $fullRegex = '#(^|/)' . $regex . '(/.*|$)#';
    } else {
      $regex = $this->patternToRegex($normalizedPattern);
      $fullRegex = '#(^|/)' . $regex . '$#';
    }

    return preg_match($fullRegex, $normalizedPath) === 1;
  }

  private function patternToRegex(string $pattern): string
  {
    $pattern = preg_quote($pattern, '#');
    $pattern = str_replace('\*', '[^/]*', $pattern);
    $pattern = str_replace('\?', '[^/]', $pattern);

    return $pattern;
  }

  private function getRelativePath(string $absolutePath): ?string
  {
    $absolutePath = str_replace('\\', '/', $absolutePath);
    $basePath = str_replace('\\', '/', $this->basePath);

    if (!str_starts_with($absolutePath, $basePath)) {
      return null;
    }

    $relative = substr($absolutePath, strlen($basePath));
    return ltrim($relative, '/');
  }
}
