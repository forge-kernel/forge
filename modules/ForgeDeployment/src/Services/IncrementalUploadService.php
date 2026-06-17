<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\FileExistenceCache;

#[Service]
final class IncrementalUploadService
{
  public function __construct(
    private readonly SshService $sshService,
    private readonly ForgeIgnoreService $ignoreService
  ) {
    $this->ignoreService->load(BASE_PATH);
  }

  public function uploadChangedFiles(array $changedFiles, string $localPath, string $remotePath, ?callable $progressCallback = null): void
  {
    if (empty($changedFiles)) {
      if ($progressCallback !== null) {
        $progressCallback('No files to upload.');
      }
      return;
    }

    $totalFiles = count($changedFiles);
    $uploadedCount = 0;
    $skippedCount = 0;

    if ($progressCallback !== null) {
      $progressCallback("Uploading {$totalFiles} changed file(s)...");
    }

    foreach ($changedFiles as $relativeFilePath) {
      $localFilePath = $localPath . '/' . $relativeFilePath;
      $remoteFilePath = $remotePath . '/' . $relativeFilePath;

      // Safety check: ensure file is not ignored (defense-in-depth)
      if ($this->ignoreService->shouldIgnore($localFilePath)) {
        $skippedCount++;
        if ($progressCallback !== null) {
          $progressCallback("Skipping ignored file: {$relativeFilePath}");
        }
        continue;
      }

// Check if local file exists (it might be a deletion)
      if (!FileExistenceCache::exists($localFilePath)) {
        $skippedCount++;
        if ($progressCallback !== null) {
          $progressCallback("Skipping deleted file: {$relativeFilePath}");
        }
        continue;
      }

      // Skip directories (git diff might include them)
      if (is_dir($localFilePath)) {
        continue;
      }

      // Upload the file
      $uploadProgress = function (int $bytesUploaded, int $totalBytes) use ($progressCallback, $relativeFilePath, $uploadedCount, $totalFiles) {
        if ($progressCallback !== null && $totalBytes > 0) {
          $percent = round(($bytesUploaded / $totalBytes) * 100);
          $progressCallback("  [{$uploadedCount}/{$totalFiles}] {$relativeFilePath} ({$percent}%)");
        }
      };

      $success = $this->sshService->upload($localFilePath, $remoteFilePath, $uploadProgress);

      if ($success) {
        $uploadedCount++;
        if ($progressCallback !== null) {
          $progressCallback("  ✓ {$relativeFilePath}");
        }
      } else {
        throw new \RuntimeException("Failed to upload file: {$relativeFilePath}");
      }
    }

    if ($progressCallback !== null) {
      $progressCallback("Upload complete: {$uploadedCount} file(s) uploaded, {$skippedCount} skipped");
    }
  }

  public function uploadFilesFromCommit(array $files, string $commitHash, string $remotePath, ?callable $progressCallback = null): void
  {
    if (empty($files)) {
      if ($progressCallback !== null) {
        $progressCallback('No files to upload.');
      }
      return;
    }

    $totalFiles = count($files);
    $uploadedCount = 0;
    $skippedCount = 0;

    if ($progressCallback !== null) {
      $progressCallback("Uploading {$totalFiles} file(s) from commit {$commitHash}...");
    }

    foreach ($files as $relativeFilePath) {
      $remoteFilePath = $remotePath . '/' . $relativeFilePath;
      $absolutePath = BASE_PATH . '/' . $relativeFilePath;

      // Safety check: ensure file is not ignored
      if ($this->ignoreService->shouldIgnore($absolutePath)) {
        $skippedCount++;
        if ($progressCallback !== null) {
          $progressCallback("Skipping ignored file: {$relativeFilePath}");
        }
        continue;
      }

      // Get file content from the specific commit using temporary file
      $tempFile = sys_get_temp_dir() . '/forge-rollback-' . uniqid() . '-' . md5($relativeFilePath);
      $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git show ' . escapeshellarg($commitHash . ':' . $relativeFilePath) . ' > ' . escapeshellarg($tempFile) . ' 2>/dev/null';
      $output = [];
      $exitCode = 0;
      @exec($command, $output, $exitCode);

      if ($exitCode !== 0 || !FileExistenceCache::exists($tempFile) || filesize($tempFile) === 0) {
        // File might not exist in that commit (was deleted), skip it
        if (FileExistenceCache::exists($tempFile)) {
          @unlink($tempFile);
        }
        $skippedCount++;
        if ($progressCallback !== null) {
          $progressCallback("Skipping file (not found in commit): {$relativeFilePath}");
        }
        continue;
      }

      // Upload the temporary file directly (handles binary files better)
      $uploadProgress = function (int $bytesUploaded, int $totalBytes) use ($progressCallback, $relativeFilePath, $uploadedCount, $totalFiles) {
        if ($progressCallback !== null && $totalBytes > 0) {
          $percent = round(($bytesUploaded / $totalBytes) * 100);
          $progressCallback("  [{$uploadedCount}/{$totalFiles}] {$relativeFilePath} ({$percent}%)");
        }
      };

      $success = $this->sshService->upload($tempFile, $remoteFilePath, $uploadProgress);

      // Clean up temporary file
      @unlink($tempFile);

      if ($success) {
        $uploadedCount++;
        if ($progressCallback !== null) {
          $progressCallback("  ✓ {$relativeFilePath}");
        }
      } else {
        throw new \RuntimeException("Failed to upload file: {$relativeFilePath}");
      }
    }

    if ($progressCallback !== null) {
      $progressCallback("Rollback complete: {$uploadedCount} file(s) uploaded, {$skippedCount} skipped");
    }
  }
}
