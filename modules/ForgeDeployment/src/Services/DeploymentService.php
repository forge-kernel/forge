<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class DeploymentService
{
  public function __construct(
    private readonly SshService $sshService,
    private readonly ForgeIgnoreService $ignoreService,
    private readonly ProjectZipService $zipService
  ) {
  }

  public function deploy(string $localPath, string $remotePath, array $commands = [], array $envVars = [], ?callable $progressCallback = null): bool
  {
    $progress = function (string $message) use ($progressCallback) {
      if ($progressCallback !== null) {
        $progressCallback($message);
      }
    };

    $this->createRemoteDirectory($remotePath);
    $this->uploadProject($localPath, $remotePath, $progress);
    $this->setPermissions($remotePath);
    $this->runCommands($remotePath, $commands, $progressCallback);

    return true;
  }

  public function connect(string $host, int $port, string $username, ?string $privateKeyPath = null, ?string $publicKeyPath = null, ?string $passphrase = null): bool
  {
    return $this->sshService->connect($host, $port, $username, $privateKeyPath, $publicKeyPath, $passphrase);
  }

  public function runPostDeploymentCommands(string $remotePath, array $commands, string $phpVersion, ?callable $outputCallback = null): void
  {
    if (empty($commands)) {
      return;
    }

    // Convert PHP version format: 8.4 -> php8.4 (keep dot)
    $phpBinary = 'php' . $phpVersion;

    foreach ($commands as $command) {
      if (str_starts_with($command, 'php forge.php')) {
        // Replace generic 'php' with specific version
        $command = str_replace('php forge.php', "{$phpBinary} forge.php", $command);
        $fullCommand = "cd {$remotePath} && {$command}";
      } elseif (str_starts_with($command, $phpBinary . ' forge.php')) {
        // Already has specific version, use as-is
        $fullCommand = "cd {$remotePath} && {$command}";
      } else {
        $fullCommand = "cd {$remotePath} && {$phpBinary} forge.php {$command}";
      }

      $result = $this->sshService->execute($fullCommand, $outputCallback, null, 1200);
      if (!$result['success']) {
        $error = $result['error'] ?: $result['output'];
        throw new \RuntimeException("Post-deployment command failed: {$command}. Error: {$error}");
      }
    }
  }

  private function createRemoteDirectory(string $remotePath): void
  {
    $this->sshService->execute("mkdir -p {$remotePath}");
  }

  private function uploadProject(string $localPath, string $remotePath, ?callable $progressCallback = null): void
  {
    $progress = function (string $message) use ($progressCallback) {
      if ($progressCallback !== null) {
        $progressCallback($message);
      }
    };

    $progress('Creating project archive...');
    $zipPath = $this->zipService->createZip($localPath, $progress);

    try {
      $fileSize = filesize($zipPath);
      $fileSizeMb = round($fileSize / 1024 / 1024, 2);
      $progress("Uploading archive ({$fileSizeMb}MB)...");

      $remoteZipPath = '/tmp/forge-deployment-' . uniqid() . '.zip';
      $this->sshService->reconnect();

      $uploadProgress = function (int $bytesUploaded, int $totalBytes) use ($progress, $fileSizeMb) {
        $percent = ($totalBytes > 0) ? round(($bytesUploaded / $totalBytes) * 100) : 0;
        $uploadedMb = round($bytesUploaded / 1024 / 1024, 2);
        $progress("Uploading: {$uploadedMb}MB / {$fileSizeMb}MB ({$percent}%)");
      };

      $this->sshService->upload($zipPath, $remoteZipPath, $uploadProgress);

      $progress('Verifying uploaded archive...');
      $verifyZip = $this->sshService->execute("test -f " . escapeshellarg($remoteZipPath) . " && echo 'ok'", null, null, 10);
      if (trim($verifyZip['output'] ?? '') !== 'ok') {
        throw new \RuntimeException('Uploaded zip file not found on server: ' . $remoteZipPath);
      }

      $progress('Checking for unzip...');
      $unzipCheck = $this->sshService->execute('which unzip', null, null, 10);
      if (!$unzipCheck['success'] || trim($unzipCheck['output']) === '') {
        $progress('Installing unzip...');
        $installResult = $this->sshService->execute('export DEBIAN_FRONTEND=noninteractive && apt-get install -y unzip', null, null, 120);
        if (!$installResult['success']) {
          $this->sshService->execute("rm -f " . escapeshellarg($remoteZipPath));
          throw new \RuntimeException('Failed to install unzip: ' . $installResult['error']);
        }
      }

      $progress('Extracting archive on server...');

      $this->sshService->execute("mkdir -p " . escapeshellarg($remotePath), null, null, 10);

      $extractCommand = sprintf(
        'cd %s && unzip -o %s 2>&1',
        escapeshellarg($remotePath),
        escapeshellarg($remoteZipPath)
      );

      $result = $this->sshService->execute($extractCommand, function ($line) use ($progress) {
        $trimmed = trim($line);
        if ($trimmed !== '' && !str_starts_with($trimmed, 'Archive:') && !str_starts_with($trimmed, 'inflating:')) {
          $progress("  " . $trimmed);
        }
      }, null, 300);

      if (!$result['success']) {
        $this->sshService->execute("rm -f " . escapeshellarg($remoteZipPath));
        throw new \RuntimeException('Failed to extract archive: ' . $result['error']);
      }

      $progress('Cleaning up temporary archive...');
      $this->sshService->execute("rm -f " . escapeshellarg($remoteZipPath), null, null, 10);

    } finally {
      $this->zipService->cleanup($zipPath);
    }
  }

  private function convertEnvValueToString(mixed $value): string
  {
    if (is_array($value)) {
      $items = array_map(function ($item) {
        return '"' . addslashes((string) $item) . '"';
      }, $value);
      return '[' . implode(', ', $items) . ']';
    }

    if (is_bool($value)) {
      return $value ? 'true' : 'false';
    }

    return (string) $value;
  }

  public function configureEnvironment(string $localPath, string $remotePath, array $envVars, ?callable $progressCallback = null, array $dbConfig = [], ?string $phpVersion = null): void
  {
    $localEnv = $localPath . '/.env';
    $localExample = $localPath . '/env-example';
    $remoteEnv = $remotePath . '/.env';

    if (!empty($dbConfig)) {
      $type = $dbConfig['database_type'] ?? 'mysql';
      if ($type === 'sqlite') {
        $envVars['DB_CONNECTION'] = 'sqlite';
        $envVars['DB_DATABASE'] = 'storage/database.sqlite';
      } else {
        $envVars['DB_CONNECTION'] = ($type === 'postgresql') ? 'pgsql' : 'mysql';
        if (isset($dbConfig['database_name']))
          $envVars['DB_DATABASE'] = $dbConfig['database_name'];
        if (isset($dbConfig['database_user']))
          $envVars['DB_USERNAME'] = $dbConfig['database_user'];
        if (isset($dbConfig['database_password']))
          $envVars['DB_PASSWORD'] = $dbConfig['database_password'];
        $envVars['DB_HOST'] = '127.0.0.1';
        $envVars['DB_PORT'] = ($type === 'postgresql') ? '5432' : '3306';
      }
    }

    $envContent = '';
    if (file_exists($localEnv)) {
      if ($progressCallback !== null) {
        $progressCallback('Reading local .env file...');
      }
      $envContent = file_get_contents($localEnv);
    } else {
      if ($progressCallback !== null) {
        $progressCallback('Local .env missing, using env-example...');
      }
      if (file_exists($localExample)) {
        $envContent = file_get_contents($localExample);
      }
    }

    $parsedEnv = $this->parseEnvFile($envContent);

    foreach ($envVars as $key => $value) {
      $parsedEnv['vars'][$key] = $this->convertEnvValueToString($value);
    }

    $mergedContent = $this->buildEnvContent($parsedEnv);

    $tempFile = sys_get_temp_dir() . '/forge-deployment-env-' . uniqid() . '.env';

    try {
      file_put_contents($tempFile, $mergedContent);

      if ($progressCallback !== null) {
        $progressCallback('Uploading merged .env file...');
      }

      $uploaded = $this->sshService->uploadString($mergedContent, $remoteEnv, $progressCallback);
      if (!$uploaded) {
        throw new \RuntimeException('Failed to upload .env file to server');
      }

      if (!file_exists($localEnv) && file_exists($localExample)) {
        $phpBinary = $phpVersion ? 'php' . $phpVersion : 'php';
        $this->sshService->execute("cd {$remotePath} && {$phpBinary} forge.php key:generate", $progressCallback);
      }
    } finally {
      if (file_exists($tempFile)) {
        @unlink($tempFile);
      }
    }
  }

  private function parseEnvFile(string $content): array
  {
    $lines = explode("\n", $content);
    $vars = [];
    $structure = [];

    foreach ($lines as $line) {
      $originalLine = $line;
      $trimmed = trim($line);

      if ($trimmed === '') {
        $structure[] = ['type' => 'empty', 'content' => ''];
        continue;
      }

      if (str_starts_with($trimmed, '#')) {
        $structure[] = ['type' => 'comment', 'content' => $originalLine];
        continue;
      }

      if (strpos($trimmed, '=') !== false) {
        $commentPos = strpos($trimmed, ' #');
        if ($commentPos !== false) {
          $lineWithoutComment = substr($trimmed, 0, $commentPos);
          $comment = substr($trimmed, $commentPos);
        } else {
          $lineWithoutComment = $trimmed;
          $comment = '';
        }

        list($key, $val) = explode('=', $lineWithoutComment, 2);
        $key = trim($key);
        $val = trim($val);

        $isArray = str_starts_with($val, '[') && str_ends_with($val, ']');
        $isBoolean = in_array(strtolower($val), ['true', 'false'], true);

        if (!$isArray && !$isBoolean) {
          if (
            (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))
          ) {
            $val = substr($val, 1, -1);
          }
        }

        $vars[$key] = $val;
        $structure[] = [
          'type' => 'var',
          'key' => $key,
          'value' => $val,
          'comment' => $comment,
          'original' => $originalLine
        ];
      } else {
        $structure[] = ['type' => 'unknown', 'content' => $originalLine];
      }
    }

    return [
      'vars' => $vars,
      'structure' => $structure
    ];
  }

  /**
   * Format environment variable value for .env file
   */
  private function formatEnvValue(string $value): string
  {
    $trimmed = trim($value);

    if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
      return $trimmed;
    }

    if (in_array(strtolower($trimmed), ['true', 'false'], true)) {
      return strtolower($trimmed);
    }

    if (preg_match('/[\s"\'=#]/', $value)) {
      return '"' . str_replace('"', '\\"', $value) . '"';
    }

    return $value;
  }

  /**
   * Build .env file content from parsed structure
   */
  private function buildEnvContent(array $parsedEnv): string
  {
    $content = '';
    $vars = $parsedEnv['vars'];
    $structure = $parsedEnv['structure'];
    $writtenVars = [];

    foreach ($structure as $item) {
      if ($item['type'] === 'empty') {
        $content .= "\n";
      } elseif ($item['type'] === 'comment') {
        $content .= $item['content'] . "\n";
      } elseif ($item['type'] === 'var') {
        $key = $item['key'];
        $value = $vars[$key] ?? $item['value'];
        $comment = $item['comment'] ?? '';

        $value = $this->formatEnvValue($value);

        $content .= "{$key}={$value}{$comment}\n";
        $writtenVars[$key] = true;
      } elseif ($item['type'] === 'unknown') {
        $content .= $item['content'] . "\n";
      }
    }

    foreach ($vars as $key => $value) {
      if (!isset($writtenVars[$key])) {
        $value = $this->formatEnvValue($value);
        $content .= "{$key}={$value}\n";
      }
    }

    return $content;
  }

  private function setPermissions(string $remotePath): void
  {
    $this->sshService->execute("chown -R www-data:www-data {$remotePath}");
    $this->sshService->execute("find {$remotePath} -type d -exec chmod 755 {} \\;");
    $this->sshService->execute("find {$remotePath} -type f -exec chmod 644 {} \\;");
    $this->sshService->execute("chmod -R 775 {$remotePath}/storage");
  }

  private function runCommands(string $remotePath, array $commands, ?callable $outputCallback = null): void
  {
    foreach ($commands as $command) {
      $fullCommand = "cd {$remotePath} && {$command}";
      $result = $this->sshService->execute($fullCommand, $outputCallback);
      if (!$result['success']) {
        throw new \RuntimeException("Command failed: {$command}. Error: {$result['error']}");
      }
    }
  }
}
