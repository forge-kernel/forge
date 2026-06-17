<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;
use ZipArchive;

#[Service]
final class ProjectZipService
{
  public function __construct(
    private readonly ForgeIgnoreService $ignoreService
  ) {
  }

  public function createZip(string $localPath, ?callable $progressCallback = null): string
  {
    $this->ignoreService->load($localPath);

    $zipPath = sys_get_temp_dir() . '/forge-deployment-' . uniqid() . '.zip';
    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      throw new \RuntimeException("Failed to create zip file: {$zipPath}");
    }

    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($localPath, \RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::SELF_FIRST
    );

    $totalFiles = 0;
    $addedFiles = 0;
    $skippedFiles = 0;

    foreach ($iterator as $item) {
      $totalFiles++;
    }

    if ($progressCallback !== null) {
      $progressCallback('Creating zip archive...');
      $progressCallback("Found {$totalFiles} files to process");
    }

    foreach ($iterator as $item) {
      $localFilePath = $item->getPathname();

      if ($this->ignoreService->shouldIgnore($localFilePath)) {
        $skippedFiles++;
        if ($progressCallback !== null && $skippedFiles <= 10) {
          $relativePath = substr($localFilePath, strlen($localPath) + 1);
          $progressCallback("Skipping: {$relativePath}");
        }
        continue;
      }

      $relativePath = substr($localFilePath, strlen($localPath) + 1);

      if ($item->isDir()) {
        $zip->addEmptyDir($relativePath);
      } else {
        $zip->addFile($localFilePath, $relativePath);
        $addedFiles++;

        if ($addedFiles % 100 === 0 && $progressCallback !== null) {
          $progress = ($addedFiles / $totalFiles) * 50;
          $progressCallback("Added {$addedFiles} files to zip ({$progress}%)");
        }
      }
    }

    $zip->close();

    $fileSize = filesize($zipPath);
    $fileSizeMb = round($fileSize / 1024 / 1024, 2);
    if ($progressCallback !== null) {
      $progressCallback("Zip created: {$fileSizeMb}MB ({$addedFiles} files, {$skippedFiles} skipped)");
    }

    return $zipPath;
  }

  public function cleanup(string $zipPath): void
  {
    if (file_exists($zipPath)) {
      unlink($zipPath);
    }
  }
}
