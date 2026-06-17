<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Services;

use Forge\Core\DI\Attributes\Service;
use ZipArchive;

#[Service]
final class ModuleFileComparisonService
{
  private const int MAX_DIFF_LINES = 50;
  private const int CONTEXT_LINES = 3;
  private const int MAX_FILES_TO_SHOW = 20;

  private ?string $extractedNewModulePath = null;

  public function compareModuleFiles(string $existingPath, string $zipPath): array
  {
    if (!is_dir($existingPath)) {
      return [
        'error' => 'Existing module path does not exist',
        'summary' => [],
        'files' => [],
      ];
    }

    if (!file_exists($zipPath)) {
      return [
        'error' => 'ZIP file does not exist',
        'summary' => [],
        'files' => [],
      ];
    }

    $tempPath = $this->extractToTemp($zipPath);
    if (!$tempPath) {
      return [
        'error' => 'Failed to extract ZIP to temporary directory',
        'summary' => [],
        'files' => [],
      ];
    }

    $this->extractedNewModulePath = $tempPath;

    try {
      $comparison = $this->performComparison($existingPath, $tempPath);
      return $comparison;
    } catch (\Throwable $e) {
      $this->cleanupTemp($tempPath);
      $this->extractedNewModulePath = null;
      return [
        'error' => 'Comparison failed: ' . $e->getMessage(),
        'summary' => [],
        'files' => [],
      ];
    }
  }

  public function getExtractedNewModulePath(): ?string
  {
    return $this->extractedNewModulePath;
  }

  public function cleanupExtractedPath(): void
  {
    if ($this->extractedNewModulePath !== null) {
      $this->cleanupTemp($this->extractedNewModulePath);
      $this->extractedNewModulePath = null;
    }
  }

  private function extractToTemp(string $zipPath): ?string
  {
    $cacheBaseDir = BASE_PATH . '/storage/framework/cache/modules';
    if (!is_dir($cacheBaseDir)) {
      if (!mkdir($cacheBaseDir, 0755, true)) {
        return null;
      }
    }

    $tempDir = $cacheBaseDir . '/tmp';
    if (is_dir($tempDir)) {
      $this->cleanupTemp($tempDir);
    }

    if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
      return null;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
      if (is_dir($tempDir)) {
        $this->cleanupTemp($tempDir);
      }
      return null;
    }

    $zip->extractTo($tempDir);
    $zip->close();

    $extractedPath = $this->findExtractedModulePath($tempDir);
    return $extractedPath ?: $tempDir;
  }

  private function findExtractedModulePath(string $tempDir): ?string
  {
    $items = scandir($tempDir);
    if ($items === false) {
      return null;
    }

    $directories = [];
    $hasModuleFile = false;
    $hasSrcDir = false;
    $hasForgeJson = false;

    foreach ($items as $item) {
      if ($item === '.' || $item === '..') {
        continue;
      }
      $path = $tempDir . '/' . $item;
      if (is_dir($path)) {
        $directories[] = $path;
        if ($item === 'src') {
          $hasSrcDir = true;
        }
      } elseif (is_file($path)) {
        if (preg_match('/Module\.php$/', $item)) {
          $hasModuleFile = true;
        }
        if ($item === 'forge.json') {
          $hasForgeJson = true;
        }
      }
    }

    if ($hasModuleFile || $hasForgeJson || $hasSrcDir) {
      return $tempDir;
    }

    if (count($directories) === 1) {
      return $directories[0];
    }

    if (count($directories) > 1) {
      foreach ($directories as $dir) {
        $dirName = basename($dir);
        if (file_exists($dir . '/forge.json') || $dirName === 'src' || !empty(glob($dir . '/*Module.php'))) {
          return $dir;
        }
      }
      return $directories[0];
    }

    return $tempDir;
  }

  private function performComparison(string $existingPath, string $newPath): array
  {
    $existingPath = rtrim(realpath($existingPath) ?: $existingPath, '/\\');
    $newPath = rtrim(realpath($newPath) ?: $newPath, '/\\');

    $existingFiles = $this->getFileList($existingPath);
    $newFiles = $this->getFileList($newPath);

    $comparison = [
      'added' => [],
      'removed' => [],
      'modified' => [],
      'unchanged' => [],
    ];

    $existingMap = [];
    foreach ($existingFiles as $file) {
      $realFile = realpath($file) ?: $file;
      $relativePath = $this->getRelativePath($realFile, $existingPath);
      if (!empty($relativePath)) {
        $existingMap[$relativePath] = $realFile;
      }
    }

    $newMap = [];
    foreach ($newFiles as $file) {
      $realFile = realpath($file) ?: $file;
      $relativePath = $this->getRelativePath($realFile, $newPath);
      if (!empty($relativePath)) {
        $newMap[$relativePath] = $realFile;
      }
    }

    $normalizedExistingMap = [];
    foreach ($existingMap as $path => $file) {
      $normalized = $this->normalizeModulePath($path);
      $normalizedExistingMap[$normalized] = ['path' => $path, 'file' => $file];
    }

    $normalizedNewMap = [];
    foreach ($newMap as $path => $file) {
      $normalized = $this->normalizeModulePath($path);
      $normalizedNewMap[$normalized] = ['path' => $path, 'file' => $file];
    }

    $allPaths = array_unique(array_merge(array_keys($normalizedExistingMap), array_keys($normalizedNewMap)));

    foreach ($allPaths as $normalizedPath) {
      $existingEntry = $normalizedExistingMap[$normalizedPath] ?? null;
      $newEntry = $normalizedNewMap[$normalizedPath] ?? null;
      $existingFile = $existingEntry['file'] ?? null;
      $newFile = $newEntry['file'] ?? null;
      $displayPath = $existingEntry['path'] ?? $newEntry['path'] ?? $normalizedPath;

      if ($existingFile === null && $newFile !== null) {
        $comparison['added'][] = [
          'path' => $displayPath,
          'size' => filesize($newFile),
        ];
      } elseif ($existingFile !== null && $newFile === null) {
        $comparison['removed'][] = [
          'path' => $displayPath,
          'size' => filesize($existingFile),
        ];
      } elseif ($existingFile !== null && $newFile !== null) {
        if ($this->isBinaryFile($existingFile) || $this->isBinaryFile($newFile)) {
          $existingSize = filesize($existingFile);
          $newSize = filesize($newFile);
          if ($existingSize !== $newSize) {
            $comparison['modified'][] = [
              'path' => $displayPath,
              'size_change' => $newSize - $existingSize,
              'old_size' => $existingSize,
              'new_size' => $newSize,
              'binary' => true,
            ];
          } else {
            $comparison['unchanged'][] = $displayPath;
          }
        } else {
          $existingHash = $this->computeFileHash($existingFile);
          $newHash = $this->computeFileHash($newFile);

          if ($existingHash === $newHash) {
            $comparison['unchanged'][] = $displayPath;
          } else {
            $diff = $this->computeFileDiff($existingFile, $newFile);
            $existingSize = filesize($existingFile);
            $newSize = filesize($newFile);
            $comparison['modified'][] = [
              'path' => $displayPath,
              'size_change' => $newSize - $existingSize,
              'old_size' => $existingSize,
              'new_size' => $newSize,
              'diff' => $diff,
              'binary' => false,
            ];
          }
        }
      }
    }

    $summary = [
      'total_new' => count($newFiles),
      'total_existing' => count($existingFiles),
      'unchanged' => count($comparison['unchanged']),
      'modified' => count($comparison['modified']),
      'added' => count($comparison['added']),
      'removed' => count($comparison['removed']),
    ];

    return [
      'summary' => $summary,
      'files' => $comparison,
    ];
  }

  private function getFileList(string $path): array
  {
    $files = [];
    $realPath = realpath($path);
    if ($realPath === false) {
      return $files;
    }

    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($realPath, \RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $filePath = $file->getRealPath();
        if ($filePath !== false) {
          $files[] = $filePath;
        }
      }
    }

    return $files;
  }

  private function computeFileHash(string $filePath): string
  {
    return hash_file('sha256', $filePath);
  }

  private function isBinaryFile(string $filePath): bool
  {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    if ($mimeType === false) {
      return true;
    }

    $textTypes = [
      'text/plain',
      'text/html',
      'text/css',
      'text/javascript',
      'application/javascript',
      'application/json',
      'application/xml',
      'text/xml',
      'text/csv',
      'application/php',
    ];

    return !in_array($mimeType, $textTypes, true) && !str_starts_with($mimeType, 'text/');
  }

  private function computeFileDiff(string $oldPath, string $newPath, int $contextLines = self::CONTEXT_LINES): array
  {
    $oldContent = file_get_contents($oldPath);
    $newContent = file_get_contents($newPath);

    if ($oldContent === false) {
      $oldContent = '';
    }
    if ($newContent === false) {
      $newContent = '';
    }

    if ($oldContent === $newContent) {
      return [];
    }

    $oldLines = explode("\n", $oldContent);
    $newLines = explode("\n", $newContent);

    $matches = $this->computeLCS($oldLines, $newLines);
    $diff = [];
    $diffCount = 0;
    $processedOld = [];
    $processedNew = [];
    $lastOldIndex = -1;
    $lastNewIndex = -1;

    foreach ($matches as $match) {
      [$oldIndex, $newIndex] = $match;

      $oldStart = $lastOldIndex + 1;
      $oldEnd = $oldIndex - 1;
      $newStart = $lastNewIndex + 1;
      $newEnd = $newIndex - 1;

      if ($oldStart <= $oldEnd || $newStart <= $newEnd) {
        if ($diffCount >= self::MAX_DIFF_LINES) {
          break;
        }

        if (
          $lastOldIndex >= 0 && $lastNewIndex >= 0 &&
          (($oldIndex - $lastOldIndex) > ($contextLines * 2 + 1) ||
            ($newIndex - $lastNewIndex) > ($contextLines * 2 + 1))
        ) {
          $diff[] = [
            'line' => null,
            'old' => null,
            'new' => null,
            'type' => 'info',
            'message' => '...',
          ];
        }

        $contextStartOld = max($oldStart, $oldIndex - $contextLines);
        $contextStartNew = max($newStart, $newIndex - $contextLines);

        for ($i = $contextStartOld; $i < $oldStart && $diffCount < self::MAX_DIFF_LINES; $i++) {
          if (!isset($processedOld[$i])) {
            $diff[] = [
              'line' => $i + 1,
              'old' => $oldLines[$i],
              'new' => $oldLines[$i],
              'type' => 'context',
            ];
            $processedOld[$i] = true;
            $diffCount++;
          }
        }

        for ($i = $oldStart; $i <= $oldEnd && $diffCount < self::MAX_DIFF_LINES; $i++) {
          if (!isset($processedOld[$i])) {
            $diff[] = [
              'line' => $i + 1,
              'old' => $oldLines[$i],
              'new' => null,
              'type' => 'removed',
            ];
            $processedOld[$i] = true;
            $diffCount++;
          }
        }

        for ($i = $newStart; $i <= $newEnd && $diffCount < self::MAX_DIFF_LINES; $i++) {
          if (!isset($processedNew[$i])) {
            $diff[] = [
              'line' => $i + 1,
              'old' => null,
              'new' => $newLines[$i],
              'type' => 'added',
            ];
            $processedNew[$i] = true;
            $diffCount++;
          }
        }

        for ($i = max($oldStart, $oldIndex - $contextLines); $i < $oldIndex && $diffCount < self::MAX_DIFF_LINES; $i++) {
          if (!isset($processedOld[$i]) && $i >= $oldStart) {
            $diff[] = [
              'line' => $i + 1,
              'old' => $oldLines[$i],
              'new' => $oldLines[$i],
              'type' => 'context',
            ];
            $processedOld[$i] = true;
            $diffCount++;
          }
        }
      }

      if ($diffCount < self::MAX_DIFF_LINES && !isset($processedOld[$oldIndex]) && !isset($processedNew[$newIndex])) {
        $diff[] = [
          'line' => $oldIndex + 1,
          'old' => $oldLines[$oldIndex],
          'new' => $newLines[$newIndex],
          'type' => 'context',
        ];
        $processedOld[$oldIndex] = true;
        $processedNew[$newIndex] = true;
        $diffCount++;
      }

      $lastOldIndex = $oldIndex;
      $lastNewIndex = $newIndex;
    }

    $oldCount = count($oldLines);
    $newCount = count($newLines);
    $lastMatchOld = $lastOldIndex >= 0 ? $lastOldIndex : -1;
    $lastMatchNew = $lastNewIndex >= 0 ? $lastNewIndex : -1;

    if ($lastMatchOld < $oldCount - 1 || $lastMatchNew < $newCount - 1) {
      $oldStart = $lastMatchOld + 1;
      $oldEnd = $oldCount - 1;
      $newStart = $lastMatchNew + 1;
      $newEnd = $newCount - 1;

      if ($diffCount >= self::MAX_DIFF_LINES) {
        return array_slice($diff, 0, self::MAX_DIFF_LINES);
      }

      if ($lastMatchOld >= 0 && $lastMatchNew >= 0) {
        $contextStartOld = max($oldStart, $oldCount - $contextLines);

        for ($i = $contextStartOld; $i < $oldStart && $diffCount < self::MAX_DIFF_LINES; $i++) {
          if (!isset($processedOld[$i])) {
            $diff[] = [
              'line' => $i + 1,
              'old' => $oldLines[$i],
              'new' => $oldLines[$i],
              'type' => 'context',
            ];
            $processedOld[$i] = true;
            $diffCount++;
          }
        }
      }

      for ($i = $oldStart; $i <= $oldEnd && $diffCount < self::MAX_DIFF_LINES; $i++) {
        if (!isset($processedOld[$i])) {
          $diff[] = [
            'line' => $i + 1,
            'old' => $oldLines[$i],
            'new' => null,
            'type' => 'removed',
          ];
          $processedOld[$i] = true;
          $diffCount++;
        }
      }

      for ($i = $newStart; $i <= $newEnd && $diffCount < self::MAX_DIFF_LINES; $i++) {
        if (!isset($processedNew[$i])) {
          $diff[] = [
            'line' => $i + 1,
            'old' => null,
            'new' => $newLines[$i],
            'type' => 'added',
          ];
          $processedNew[$i] = true;
          $diffCount++;
        }
      }
    }

    return array_slice($diff, 0, self::MAX_DIFF_LINES);
  }

  private function computeLCS(array $oldLines, array $newLines): array
  {
    $oldCount = count($oldLines);
    $newCount = count($newLines);

    if ($oldCount === 0 || $newCount === 0) {
      return [];
    }

    $dp = array_fill(0, $oldCount + 1, array_fill(0, $newCount + 1, 0));

    for ($i = 1; $i <= $oldCount; $i++) {
      for ($j = 1; $j <= $newCount; $j++) {
        if ($oldLines[$i - 1] === $newLines[$j - 1]) {
          $dp[$i][$j] = $dp[$i - 1][$j - 1] + 1;
        } else {
          $dp[$i][$j] = max($dp[$i - 1][$j], $dp[$i][$j - 1]);
        }
      }
    }

    $matches = [];
    $i = $oldCount;
    $j = $newCount;

    while ($i > 0 && $j > 0) {
      if ($oldLines[$i - 1] === $newLines[$j - 1]) {
        $matches[] = [$i - 1, $j - 1];
        $i--;
        $j--;
      } elseif ($dp[$i - 1][$j] > $dp[$i][$j - 1]) {
        $i--;
      } else {
        $j--;
      }
    }

    return array_reverse($matches);
  }

  private function cleanupTemp(string $path): void
  {
    if (!is_dir($path)) {
      return;
    }

    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
      if ($file->isDir()) {
        rmdir($file->getRealPath());
      } else {
        unlink($file->getRealPath());
      }
    }

    rmdir($path);
  }

  public function formatComparisonTable(array $comparison): string
  {
    if (isset($comparison['error'])) {
      return '';
    }

    $output = [];
    $summary = $comparison['summary'] ?? [];
    $files = $comparison['files'] ?? [];

    $output[] = "Comparison Summary:";
    $output[] = sprintf("  Total files in new version: %d", $summary['total_new'] ?? 0);
    $output[] = sprintf("  Total files in existing version: %d", $summary['total_existing'] ?? 0);
    $output[] = sprintf("  Files unchanged: %d", $summary['unchanged'] ?? 0);
    $output[] = sprintf("  Files modified: %d", $summary['modified'] ?? 0);
    $output[] = sprintf("  Files added: %d", $summary['added'] ?? 0);
    $output[] = sprintf("  Files removed: %d", $summary['removed'] ?? 0);
    $output[] = "";

    $allChanges = [];
    foreach ($files['modified'] ?? [] as $file) {
      $allChanges[] = ['type' => 'modified', 'data' => $file];
    }
    foreach ($files['added'] ?? [] as $file) {
      $allChanges[] = ['type' => 'added', 'data' => $file];
    }
    foreach ($files['removed'] ?? [] as $file) {
      $allChanges[] = ['type' => 'removed', 'data' => $file];
    }

    if (empty($allChanges)) {
      $output[] = "No changes detected - files are identical.";
      return implode("\n", $output);
    }

    $output[] = "Changed Files:";
    $output[] = $this->createTable($allChanges);

    $modifiedFiles = array_slice($files['modified'] ?? [], 0, self::MAX_FILES_TO_SHOW);
    foreach ($modifiedFiles as $file) {
      if (!($file['binary'] ?? false) && isset($file['diff']) && !empty($file['diff'])) {
        $output[] = "";
        $output[] = sprintf("Preview of changes in %s:", $file['path']);
        foreach ($file['diff'] as $diffLine) {
          if ($diffLine['type'] === 'info') {
            $output[] = "  " . $diffLine['message'];
          } elseif ($diffLine['type'] === 'context') {
            $output[] = sprintf("    Line %d: %s", $diffLine['line'], $this->truncateLine($diffLine['old'] ?? ''));
          } elseif ($diffLine['type'] === 'removed') {
            $output[] = sprintf("  - Line %d: %s", $diffLine['line'], $this->truncateLine($diffLine['old'] ?? ''));
          } elseif ($diffLine['type'] === 'added') {
            $output[] = sprintf("  + Line %d: %s", $diffLine['line'], $this->truncateLine($diffLine['new'] ?? ''));
          } elseif ($diffLine['type'] === 'modified') {
            $output[] = sprintf("  - Line %d: %s", $diffLine['line'], $this->truncateLine($diffLine['old'] ?? ''));
            $output[] = sprintf("  + Line %d: %s", $diffLine['line'], $this->truncateLine($diffLine['new'] ?? ''));
          }
        }
      }
    }

    if (count($files['modified'] ?? []) > self::MAX_FILES_TO_SHOW) {
      $output[] = "";
      $output[] = sprintf(
        "... and %d more modified files (showing first %d)",
        count($files['modified'] ?? []) - self::MAX_FILES_TO_SHOW,
        self::MAX_FILES_TO_SHOW
      );
    }

    return implode("\n", $output);
  }

  public function displayInteractiveComparison(array $comparison, ?\Forge\Core\Services\InteractiveSelect $interactiveSelect = null): array
  {
    if (isset($comparison['error'])) {
      echo $comparison['error'] . "\n";
      return [];
    }

    $summary = $comparison['summary'] ?? [];
    $files = $comparison['files'] ?? [];

    echo "Comparison Summary:\n";
    echo sprintf("  Total files in new version: %d\n", $summary['total_new'] ?? 0);
    echo sprintf("  Total files in existing version: %d\n", $summary['total_existing'] ?? 0);
    echo sprintf("  Files unchanged: %d\n", $summary['unchanged'] ?? 0);
    echo sprintf("  Files modified: %d\n", $summary['modified'] ?? 0);
    echo sprintf("  Files added: %d\n", $summary['added'] ?? 0);
    echo sprintf("  Files removed: %d\n", $summary['removed'] ?? 0);
    echo "\n";

    $allChanges = [];
    foreach ($files['modified'] ?? [] as $file) {
      $allChanges[] = ['type' => 'modified', 'data' => $file];
    }
    foreach ($files['added'] ?? [] as $file) {
      $allChanges[] = ['type' => 'added', 'data' => $file];
    }
    foreach ($files['removed'] ?? [] as $file) {
      $allChanges[] = ['type' => 'removed', 'data' => $file];
    }

    if (empty($allChanges)) {
      echo "No changes detected - files are identical.\n";
      return [];
    }

    while (true) {
      echo "Changed Files:\n";
      echo $this->createTable($allChanges);
      echo "\n";

      $options = [];
      foreach ($allChanges as $change) {
        $file = $change['data'];
        $path = $file['path'] ?? '';
        $status = ucfirst($change['type']);
        $hasDiff = !($file['binary'] ?? false) && isset($file['diff']) && !empty($file['diff']);

        $option = $path;
        if ($hasDiff) {
          $option .= " ({$status})";
        } else {
          $option .= " ({$status} - " . ($file['binary'] ?? false ? 'binary' : 'no diff') . ")";
        }
        $options[] = $option;
      }
      $options[] = "Continue with installation";

      if ($interactiveSelect) {
        $selectedIndex = $interactiveSelect->select($options, "Select a file to view changes (or continue)");
      } else {
        echo "\n";
        foreach ($options as $index => $option) {
          echo sprintf("  [%d] %s\n", $index + 1, $option);
        }
        echo "\n";
        $input = readline("Enter number (1-" . count($options) . "): ");
        $selectedIndex = is_numeric($input) ? (int) $input - 1 : null;
      }

      if ($selectedIndex === null || $selectedIndex < 0 || $selectedIndex >= count($options)) {
        break;
      }

      if ($selectedIndex === count($options) - 1) {
        break;
      }

      $selectedChange = $allChanges[$selectedIndex];
      $selectedFile = $selectedChange['data'];

      echo "\n";
      echo sprintf("Preview of changes in %s:\n", $selectedFile['path']);
      echo str_repeat("─", 80) . "\n";

      if ($selectedFile['binary'] ?? false) {
        echo "  (Binary file - cannot show diff)\n";
        if (isset($selectedFile['size_change'])) {
          $sizeChange = $selectedFile['size_change'];
          echo sprintf("  Size change: %s\n", $this->formatBytes($sizeChange));
        }
      } elseif (isset($selectedFile['diff']) && !empty($selectedFile['diff'])) {
        foreach ($selectedFile['diff'] as $diffLine) {
          if ($diffLine['type'] === 'info') {
            echo "  " . $diffLine['message'] . "\n";
          } elseif ($diffLine['type'] === 'context') {
            echo sprintf("    Line %d: %s\n", $diffLine['line'], $this->truncateLine($diffLine['old'] ?? ''));
          } elseif ($diffLine['type'] === 'removed') {
            echo sprintf("  - Line %d: %s\n", $diffLine['line'], $this->truncateLine($diffLine['old'] ?? ''));
          } elseif ($diffLine['type'] === 'added') {
            echo sprintf("  + Line %d: %s\n", $diffLine['line'], $this->truncateLine($diffLine['new'] ?? ''));
          } elseif ($diffLine['type'] === 'modified') {
            echo sprintf("  - Line %d: %s\n", $diffLine['line'], $this->truncateLine($diffLine['old'] ?? ''));
            echo sprintf("  + Line %d: %s\n", $diffLine['line'], $this->truncateLine($diffLine['new'] ?? ''));
          }
        }
      } else {
        echo "  (No diff available)\n";
      }

      echo str_repeat("─", 80) . "\n";
      echo "\n";
      echo "Press Enter to return to file list...";
      fgets(STDIN);

      if (function_exists('posix_isatty') && posix_isatty(STDOUT)) {
        echo "\033[2J\033[H";
      } else {
        echo "\n\n";
      }
    }

    return $allChanges;
  }

  public function selectFilesForPreservation(array $modifiedFiles, ?\Forge\Core\Services\InteractiveSelect $interactiveSelect = null): array
  {
    if (empty($modifiedFiles)) {
      return [];
    }

    $options = [];
    $fileMap = [];

    foreach ($modifiedFiles as $change) {
      if ($change['type'] !== 'modified') {
        continue;
      }

      $file = $change['data'];
      $path = $file['path'] ?? '';

      if ($file['binary'] ?? false) {
        continue;
      }

      if (!isset($file['diff']) || empty($file['diff'])) {
        continue;
      }

      $fileMap[] = $change;
      $options[] = $path;
    }

    if (empty($options)) {
      return [];
    }

    echo "\n";
    echo "┌─────────────────────────────────────────────────────────────────┐\n";
    echo "│ Do you want to preserve your modifications?                     │\n";
    echo "│                                                                 │\n";
    echo "│ This will apply your changes to the new version, keeping your   │\n";
    echo "│ customizations. Your modifications will take precedence.        │\n";
    echo "└─────────────────────────────────────────────────────────────────┘\n";
    echo "\n";

    $preserveChoice = readline("Preserve modifications? [Y/n]: ");
    $preserveChoice = strtolower(trim($preserveChoice));

    if ($preserveChoice !== '' && $preserveChoice !== 'y' && $preserveChoice !== 'yes') {
      return [];
    }

    echo "\n";
    echo "Select files to preserve modifications:\n";
    echo "\n";

    if ($interactiveSelect) {
      $selectedIndices = $interactiveSelect->multiSelect($options, "Select files (Space to toggle, Enter to confirm)", []);
    } else {
      echo "Files with modifications:\n";
      foreach ($options as $index => $option) {
        echo sprintf("  [%d] %s\n", $index + 1, $option);
      }
      echo "\n";
      $input = readline("Enter numbers separated by commas (e.g., 1,3,5) or 'all' for all: ");
      $input = trim($input);

      if (strtolower($input) === 'all') {
        $selectedIndices = array_keys($options);
      } else {
        $selectedIndices = [];
        $numbers = explode(',', $input);
        foreach ($numbers as $num) {
          $num = trim($num);
          if (is_numeric($num)) {
            $idx = (int)$num - 1;
            if ($idx >= 0 && $idx < count($options)) {
              $selectedIndices[] = $idx;
            }
          }
        }
      }
    }

    $selectedFiles = [];
    foreach ($selectedIndices as $index) {
      if (isset($fileMap[$index])) {
        $selectedFiles[] = $fileMap[$index];
      }
    }

    if (!empty($selectedFiles)) {
      echo "\n";
      echo sprintf("✓ %d file(s) selected for modification preservation.\n", count($selectedFiles));
    }

    return $selectedFiles;
  }

  public function preserveUserModifications(array $selectedFiles, string $existingModulePath, string $newModulePath, string $preservedPath): array
  {
    $preservedFiles = [];
    $errors = [];

    if (!is_dir($preservedPath)) {
      if (!mkdir($preservedPath, 0755, true)) {
        $errors[] = "Failed to create preservation directory: {$preservedPath}";
        return ['files' => [], 'errors' => $errors];
      }
    }

    foreach ($selectedFiles as $change) {
      $file = $change['data'];
      $relativePath = $file['path'] ?? '';

      if (empty($relativePath)) {
        continue;
      }

      $existingFilePath = $existingModulePath . '/' . $relativePath;
      $newFilePath = $newModulePath . '/' . $relativePath;
      $preservedFilePath = $preservedPath . '/' . $relativePath;

      if (!file_exists($existingFilePath)) {
        $errors[] = "Existing file not found: {$relativePath}";
        continue;
      }

      if (!file_exists($newFilePath)) {
        $errors[] = "New file not found: {$relativePath}";
        continue;
      }

      try {
        $mergedContent = $this->applyUserModificationsToFile($existingFilePath, $newFilePath);

        if ($mergedContent === null) {
          $errors[] = "Failed to merge file: {$relativePath}";
          continue;
        }

        $preservedDir = dirname($preservedFilePath);
        if (!is_dir($preservedDir)) {
          if (!mkdir($preservedDir, 0755, true)) {
            $errors[] = "Failed to create directory: {$preservedDir}";
            continue;
          }
        }

        if (file_put_contents($preservedFilePath, $mergedContent) === false) {
          $errors[] = "Failed to write preserved file: {$relativePath}";
          continue;
        }

        $preservedFiles[] = $relativePath;
      } catch (\Throwable $e) {
        $errors[] = "Error merging file {$relativePath}: " . $e->getMessage();
        continue;
      }
    }

    return ['files' => $preservedFiles, 'errors' => $errors];
  }

  private function applyUserModificationsToFile(string $userFilePath, string $newFilePath): ?string
  {
    $userContent = file_get_contents($userFilePath);
    $newContent = file_get_contents($newFilePath);

    if ($userContent === false || $newContent === false) {
      return null;
    }

    if ($userContent === $newContent) {
      return $newContent;
    }

    $userLines = explode("\n", $userContent);
    $newLines = explode("\n", $newContent);

    $mergedLines = $this->mergeUserChangesIntoNew($userLines, $newLines);

    return implode("\n", $mergedLines);
  }

  private function mergeUserChangesIntoNew(array $userLines, array $newLines): array
  {
    $matches = $this->computeLCS($newLines, $userLines);
    $mergedLines = [];

    $newIndex = 0;
    $userIndex = 0;
    $matchIndex = 0;

    while ($newIndex < count($newLines) || $userIndex < count($userLines)) {
      if ($matchIndex < count($matches)) {
        [$matchNew, $matchUser] = $matches[$matchIndex];

        while ($newIndex < $matchNew || $userIndex < $matchUser) {
          if ($newIndex < $matchNew && $userIndex < $matchUser) {
            $mergedLines[] = $userLines[$userIndex];
            $newIndex++;
            $userIndex++;
          } elseif ($userIndex < $matchUser) {
            $mergedLines[] = $userLines[$userIndex];
            $userIndex++;
          } else {
            $newIndex++;
          }
        }

        if ($newIndex < count($newLines) && $userIndex < count($userLines)) {
          $mergedLines[] = $userLines[$userIndex];
          $newIndex++;
          $userIndex++;
        }

        $matchIndex++;
      } else {
        while ($userIndex < count($userLines)) {
          $mergedLines[] = $userLines[$userIndex];
          $userIndex++;
        }
        break;
      }
    }

    return $mergedLines;
  }

  private function createTable(array $changes): string
  {
    $rows = [];
    $rows[] = ['File Path', 'Status', 'Size Change'];

    foreach (array_slice($changes, 0, self::MAX_FILES_TO_SHOW) as $change) {
      $file = $change['data'];
      $path = $file['path'] ?? '';
      $status = ucfirst($change['type']);

      $sizeChange = '';
      if (isset($file['size_change'])) {
        $change = $file['size_change'];
        if ($change > 0) {
          $sizeChange = '+' . $this->formatBytes($change);
        } elseif ($change < 0) {
          $sizeChange = $this->formatBytes($change);
        } else {
          $sizeChange = '0 bytes';
        }
      } elseif (isset($file['size'])) {
        $sizeChange = $this->formatBytes($file['size']);
      }

      $rows[] = [$path, $status, $sizeChange];
    }

    $colWidths = [0, 0, 0];
    foreach ($rows as $row) {
      $colWidths[0] = max($colWidths[0], mb_strlen($row[0]));
      $colWidths[1] = max($colWidths[1], mb_strlen($row[1]));
      $colWidths[2] = max($colWidths[2], mb_strlen($row[2]));
    }

    $colWidths[0] = min($colWidths[0], 50);
    $colWidths[1] = max($colWidths[1], 10);
    $colWidths[2] = max($colWidths[2], 12);

    $totalWidth = $colWidths[0] + $colWidths[1] + $colWidths[2] + 8;

    $output = [];
    $output[] = '┌' . str_repeat('─', $colWidths[0] + 2) . '┬' . str_repeat('─', $colWidths[1] + 2) . '┬' . str_repeat('─', $colWidths[2] + 2) . '┐';

    foreach ($rows as $index => $row) {
      $path = mb_substr($row[0], 0, $colWidths[0]);
      $status = str_pad($row[1], $colWidths[1], ' ', STR_PAD_RIGHT);
      $size = str_pad($row[2], $colWidths[2], ' ', STR_PAD_RIGHT);

      $output[] = '│ ' . str_pad($path, $colWidths[0], ' ', STR_PAD_RIGHT) . ' │ ' . $status . ' │ ' . $size . ' │';

      if ($index === 0) {
        $output[] = '├' . str_repeat('─', $colWidths[0] + 2) . '┼' . str_repeat('─', $colWidths[1] + 2) . '┼' . str_repeat('─', $colWidths[2] + 2) . '┤';
      }
    }

    $output[] = '└' . str_repeat('─', $colWidths[0] + 2) . '┴' . str_repeat('─', $colWidths[1] + 2) . '┴' . str_repeat('─', $colWidths[2] + 2) . '┘';

    return implode("\n", $output);
  }

  private function formatBytes(int $bytes): string
  {
    if ($bytes === 0) {
      return '0 bytes';
    }

    $sign = $bytes < 0 ? '-' : '';
    $bytes = abs($bytes);

    $units = ['bytes', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    $size = $bytes;

    while ($size >= 1024 && $unitIndex < count($units) - 1) {
      $size /= 1024;
      $unitIndex++;
    }

    return $sign . round($size, 2) . ' ' . $units[$unitIndex];
  }

  private function truncateLine(?string $line, int $maxLength = 80): string
  {
    if ($line === null) {
      return '';
    }

    if (mb_strlen($line) <= $maxLength) {
      return $line;
    }

    return mb_substr($line, 0, $maxLength - 3) . '...';
  }

  private function getRelativePath(string $filePath, string $basePath): string
  {
    $filePath = str_replace('\\', '/', $filePath);
    $basePath = str_replace('\\', '/', $basePath);

    $basePath = rtrim($basePath, '/');
    $filePath = rtrim($filePath, '/');

    if (str_starts_with($filePath, $basePath)) {
      $relative = substr($filePath, strlen($basePath));
      $relative = ltrim($relative, '/');
      return $relative;
    }

    return $filePath;
  }

  private function normalizeModulePath(string $path): string
  {
    $normalized = $path;

    $normalized = str_replace('\\', '/', $normalized);
    $normalized = ltrim($normalized, '/');

    $normalized = preg_replace('#^private(Contracts|Resources|Commands|Services|Controllers|Models|Events|Middlewares|Tests|Dto|Seeders|Migrations)/#i', 'src/$1/', $normalized);

    $normalized = preg_replace('#^private([A-Z][a-zA-Z]*Module\.php)$#i', 'src/$1', $normalized);

    if (!str_starts_with($normalized, 'src/')) {
      if (preg_match('#^(Contracts|Resources|Commands|Services|Controllers|Models|Events|Middlewares|Tests|Dto|Seeders|Migrations)/#', $normalized)) {
        $normalized = 'src/' . $normalized;
      } elseif (preg_match('#^([A-Z][a-zA-Z]*Module\.php)$#', $normalized)) {
        $normalized = 'src/' . $normalized;
      }
    }

    $normalized = preg_replace('#/+#', '/', $normalized);

    return $normalized;
  }
}
