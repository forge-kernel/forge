<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\FileExistenceCache;

#[Service]
final class SshService
{
  private mixed $connection = null;
  private mixed $sftp = null;
  private string $host;
  private int $port;
  private string $username;
  private ?string $privateKeyPath = null;
  private ?string $publicKeyPath = null;
  private ?string $passphrase = null;

  public function connect(string $host, int $port, string $username, ?string $privateKeyPath = null, ?string $publicKeyPath = null, ?string $passphrase = null): bool
  {
    if (!function_exists('ssh2_connect') && !extension_loaded('ssh2')) {
      throw new \RuntimeException('SSH2 extension is not available. Install php-ssh2 extension.');
    }

    $this->host = $host;
    $this->port = $port;
    $this->username = $username;
    $this->privateKeyPath = $privateKeyPath;
    $this->publicKeyPath = $publicKeyPath;
    $this->passphrase = $passphrase;

    $this->connection = @ssh2_connect($host, $port, [], [
      'hostkey' => 'ssh-rsa,ssh-dss,ecdsa-sha2-nistp256,ecdsa-sha2-nistp384,ecdsa-sha2-nistp521,ssh-ed25519',
    ]);

    if (!$this->connection || !is_resource($this->connection)) {
      $error = error_get_last();
      throw new \RuntimeException("Failed to connect to {$host}:{$port}. " . ($error['message'] ?? 'Connection refused or timeout'));
    }

    $authenticated = false;

    if ($privateKeyPath !== null && file_exists($privateKeyPath)) {
      $pubKey = $publicKeyPath ?? ($privateKeyPath . '.pub');
      if (file_exists($pubKey)) {
        $authenticated = @ssh2_auth_pubkey_file(
          $this->connection,
          $username,
          $pubKey,
          $privateKeyPath,
          $passphrase ?? ''
        );
        if (!$authenticated) {
          $error = error_get_last();
          throw new \RuntimeException("SSH key authentication failed for {$username}@{$host}. " . ($error['message'] ?? 'Invalid key or permissions'));
        }
      } else {
        throw new \RuntimeException("Public key file not found: {$pubKey}");
      }
    } else {
      throw new \RuntimeException("Private key file not found: {$privateKeyPath}");
    }

    if (!$authenticated) {
      $this->disconnect();
      throw new \RuntimeException("SSH authentication failed for {$username}@{$host}");
    }

    $this->sftp = ssh2_sftp($this->connection);
    if ($this->sftp === false) {
      $this->disconnect();
      throw new \RuntimeException("Failed to initialize SFTP subsystem on {$host}");
    }

    return true;
  }

  public function execute(string $command, ?callable $outputCallback = null, ?callable $errorCallback = null, int $timeout = 600): array
  {
    if ($this->connection === null || !is_resource($this->connection)) {
      throw new \RuntimeException('SSH connection not established or invalid');
    }

    $displayCommand = $this->sanitizeCommand($command);
    if ($outputCallback !== null) {
      $outputCallback("      [SSH] Running: {$displayCommand}");
    }

    // Append exit code capture to command
    $exitMarker = "---FORGE_EXIT_CODE---";
    $commandWithExitCode = "{$command}; FORGE_RET=\$?; echo \"\"; echo \"{$exitMarker}:\$FORGE_RET\"";

    $stream = @ssh2_exec($this->connection, $commandWithExitCode);
    if (!$stream || !is_resource($stream)) {
      $error = error_get_last();
      $errorMsg = $error['message'] ?? 'Unknown error';

      // Try to reconnect once on channel failure
      if (strpos($errorMsg, 'channel') !== false || strpos($errorMsg, 'request') !== false) {
        if ($outputCallback !== null) {
          $outputCallback('      Reconnecting SSH due to channel error...');
        }
        $this->reconnect();
        $stream = @ssh2_exec($this->connection, $commandWithExitCode);
      }

      if (!$stream || !is_resource($stream)) {
        throw new \RuntimeException("Failed to execute command: {$errorMsg}");
      }
    }

    stream_set_blocking($stream, false);
    $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    stream_set_blocking($errorStream, false);

    $output = '';
    $errorOutput = '';
    $startTime = time();
    $lastActivityTime = time();
    $exitCode = null;
    $maxIdleTime = 60;

    while (true) {
      $currentTime = time();
      if ($currentTime - $startTime > $timeout) {
        @fclose($stream);
        @fclose($errorStream);
        throw new \RuntimeException("Command timed out after {$timeout}s: {$displayCommand}");
      }

      $read = [$stream, $errorStream];
      $write = $except = null;
      $changed = @stream_select($read, $write, $except, 1);

      if ($changed === false) {
        // Error in stream_select, possibly interrupted
        usleep(100000);
        continue;
      }

      $hasData = false;
      foreach ([$stream, $errorStream] as $idx => $s) {
        while ($line = fgets($s)) {
          $hasData = true;
          $lastActivityTime = time();
          $trimmed = trim($line);

          if ($s === $stream) {
            if (strpos($trimmed, $exitMarker . ':') !== false) {
              if (preg_match('/' . preg_quote($exitMarker) . ':(\d+)/', $trimmed, $matches)) {
                $exitCode = (int)$matches[1];
                continue;
              }
            }
            $output .= $line;
            if ($outputCallback !== null && $trimmed !== '') {
              $outputCallback($trimmed);
            }
          } else {
            $errorOutput .= $line;
            if ($errorCallback !== null && $trimmed !== '') {
              $errorCallback($trimmed);
            }
          }
        }
      }

      if ($exitCode !== null) {
        break;
      }

      if (feof($stream) && feof($errorStream) && !$hasData) {
        break;
      }

      if (!$hasData && ($currentTime - $lastActivityTime > $maxIdleTime)) {
        // Only timeout if we've been idle for a long time
        break;
      }

      usleep(50000);
    }

    @fclose($stream);
    @fclose($errorStream);

    if ($exitCode === null) {
      $exitCode = (empty($errorOutput) || strpos(strtolower($errorOutput), 'error') === false) ? 0 : 1;
    }

    return [
      'success' => $exitCode === 0,
      'output' => trim($output),
      'error' => trim($errorOutput),
      'exit_code' => $exitCode
    ];
  }

  private function sanitizeCommand(string $command): string
  {
    $patterns = [
      '/password\s+[^\s]+/i' => 'password ***',
      '/passwd\s+[^\s]+/i' => 'passwd ***',
      '/-p\s+[^\s]+/i' => '-p ***',
      '/--password\s+[^\s]+/i' => '--password ***',
    ];

    foreach ($patterns as $pattern => $replacement) {
      $command = preg_replace($pattern, $replacement, $command);
    }

    return $command;
  }

  private function getExitCode(): int
  {
    $exitStream = @ssh2_exec($this->connection, 'echo $?');
    if (!$exitStream || !is_resource($exitStream)) {
      return 1;
    }

    stream_set_blocking($exitStream, true);
    $exitCode = (int)trim(stream_get_contents($exitStream));
    fclose($exitStream);

    return $exitCode;
  }

  public function upload(string $localPath, string $remotePath, ?callable $progressCallback = null): bool
  {
    $this->ensureSftpConnection();

    if (!file_exists($localPath)) {
      return false;
    }

    $remoteDir = dirname($remotePath);
    $this->createRemoteDirectory($remoteDir);

    $sftpId = (int)$this->sftp;
    $stream = @fopen("ssh2.sftp://{$sftpId}{$remotePath}", 'w');
    if (!$stream || !is_resource($stream)) {
      $this->reinitializeSftp();
      $sftpId = (int)$this->sftp;
      $stream = @fopen("ssh2.sftp://{$sftpId}{$remotePath}", 'w');
      if (!$stream || !is_resource($stream)) {
      return false;
      }
    }

    $localStream = fopen($localPath, 'r');
    if (!$localStream) {
      fclose($stream);
      return false;
    }

    $fileSize = filesize($localPath);
    $bytesUploaded = 0;
    $chunkSize = 8192;
    $lastProgressUpdate = 0;

    while (!feof($localStream)) {
      $chunk = fread($localStream, $chunkSize);
      if ($chunk === false) {
        break;
      }

      $written = fwrite($stream, $chunk);
      if ($written === false) {
        fclose($stream);
        fclose($localStream);
        return false;
      }

      $bytesUploaded += $written;

      if ($progressCallback !== null && ($bytesUploaded - $lastProgressUpdate) >= (1024 * 1024)) {
        $progressCallback($bytesUploaded, $fileSize);
        $lastProgressUpdate = $bytesUploaded;
      }
    }

    if ($progressCallback !== null && $bytesUploaded > $lastProgressUpdate) {
      $progressCallback($bytesUploaded, $fileSize);
    }

    fclose($stream);
    fclose($localStream);

    return true;
  }

  public function uploadString(string $content, string $remotePath, ?callable $outputCallback = null): bool
  {
    if ($outputCallback !== null) {
      $outputCallback("      [SSH] Uploading configuration to {$remotePath}...");
    }

    $uploaded = false;
    // Try SFTP first
    try {
      $this->ensureSftpConnection();
      $remoteDir = dirname($remotePath);
      $this->createRemoteDirectory($remoteDir);

      $sftpId = (int)$this->sftp;
      $stream = @fopen("ssh2.sftp://{$sftpId}{$remotePath}", 'w');

      if ($stream && is_resource($stream)) {
        $bytesWritten = @fwrite($stream, $content);
        @fclose($stream);

        if ($bytesWritten !== false) {
          // Verification
          $verify = $this->execute("test -f " . escapeshellarg($remotePath) . " && echo 'exists' || echo 'missing'", null, null, 10);
          if (trim($verify['output'] ?? '') === 'exists') {
            $uploaded = true;
          }
        }
      }
    } catch (\Exception $e) {
      // SFTP failed
    }

    if (!$uploaded) {
      if ($outputCallback !== null) {
        $outputCallback("      [SSH] SFTP upload failed, using SSH fallback...");
      }
      $uploaded = $this->uploadStringViaSsh($content, $remotePath, $outputCallback);
    }

    return $uploaded;
  }

  private function uploadStringViaSsh(string $content, string $remotePath, ?callable $outputCallback = null): bool
  {
    $base64Content = base64_encode($content);

    // Use a more robust command structure
    $command = sprintf(
      'echo %s | base64 -d > %s',
      escapeshellarg($base64Content),
      escapeshellarg($remotePath)
    );

    $result = $this->execute($command, $outputCallback, null, 60);

    if (!$result['success']) {
      throw new \RuntimeException("Failed to upload file via SSH: {$remotePath}. Error: " . ($result['error'] ?? 'Unknown error'));
    }

    // Double check verification
    $verifyResult = $this->execute(sprintf('test -f %s && echo "exists" || echo "missing"', escapeshellarg($remotePath)), null, null, 10);
    if (trim($verifyResult['output'] ?? '') !== 'exists') {
      throw new \RuntimeException("File upload verification failed: {$remotePath} was not created");
    }

    return true;
  }

  private function ensureSftpConnection(): void
  {
    if ($this->connection === null || !is_resource($this->connection)) {
      throw new \RuntimeException('SSH connection not established');
    }

    if ($this->sftp === null || !is_resource($this->sftp)) {
      $this->reinitializeSftp();
    }
  }

  private function reinitializeSftp(): void
  {
    if ($this->connection === null || !is_resource($this->connection)) {
      throw new \RuntimeException('Cannot reinitialize SFTP: SSH connection not established');
    }

    // Wait a moment before retrying to avoid "Would block" errors
    usleep(500000); // 500ms

    $this->sftp = @ssh2_sftp($this->connection);
    if ($this->sftp === false || !is_resource($this->sftp)) {
      $error = error_get_last();
      $errorMsg = $error['message'] ?? 'Unknown error';
      // Don't throw exception here - let the fallback method handle it
      // Just set sftp to null so fallback is used
      $this->sftp = null;
    }
  }

  public function download(string $remotePath, string $localPath): bool
  {
    if ($this->sftp === null) {
      throw new \RuntimeException('SFTP connection not established');
    }

    $stream = fopen("ssh2.sftp://{$this->sftp}{$remotePath}", 'r');
    if (!$stream) {
      return false;
    }

    $localDir = dirname($localPath);
    if (!is_dir($localDir)) {
      mkdir($localDir, 0755, true);
    }

    $localStream = fopen($localPath, 'w');
    if (!$localStream) {
      fclose($stream);
      return false;
    }

    $result = stream_copy_to_stream($stream, $localStream) !== false;
    fclose($stream);
    fclose($localStream);

    return $result;
  }

  public function reconnect(): bool
  {
    if ($this->host === null || $this->privateKeyPath === null) {
      throw new \RuntimeException('Cannot reconnect: Connection details not stored');
    }

    $this->disconnect();
    return $this->connect(
      $this->host,
      $this->port,
      $this->username,
      $this->privateKeyPath,
      $this->publicKeyPath,
      $this->passphrase
    );
  }

  public function disconnect(): void
  {
    if ($this->connection !== null) {
      $this->connection = null;
      $this->sftp = null;
    }
  }

  public function isConnected(): bool
  {
    return $this->connection !== null && $this->sftp !== null;
  }

  private function createRemoteDirectory(string $remoteDir): void
  {
    // For /tmp, the directory already exists, so skip creation
    if ($remoteDir === '/tmp' || $remoteDir === '/tmp/') {
      return;
    }

    // Ensure SFTP connection is valid
    try {
      $this->ensureSftpConnection();
    } catch (\RuntimeException $e) {
      // If SFTP connection can't be established, skip directory creation
      // The file upload will fail with a clearer error message
      return;
    }

    $sftpId = (int)$this->sftp;
    $parts = explode('/', trim($remoteDir, '/'));
    $currentPath = '';

    foreach ($parts as $part) {
      if ($part === '') {
        continue;
      }
      $currentPath .= '/' . $part;
      $dirPath = "ssh2.sftp://{$sftpId}{$currentPath}";
      if (!@is_dir($dirPath)) {
        if (!@mkdir($dirPath, 0755, true)) {
          // Directory creation failed, but continue - might already exist or permissions issue
          // The actual file write will fail if there's a real problem
        }
      }
    }
  }
}
