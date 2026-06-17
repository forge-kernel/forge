<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class GitDiffService
{
  public function __construct(
    private readonly ForgeIgnoreService $ignoreService
  ) {
    $this->ignoreService->load(BASE_PATH);
  }

  public function isGitRepository(): bool
  {
    $gitDir = BASE_PATH . '/.git';
    return is_dir($gitDir);
  }

  public function getCurrentCommitHash(): ?string
  {
    if (!$this->isGitRepository()) {
      return null;
    }

    $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git rev-parse HEAD 2>/dev/null';
    $output = [];
    $exitCode = 0;
    @exec($command, $output, $exitCode);

    if ($exitCode !== 0 || empty($output)) {
      return null;
    }

    return trim($output[0]);
  }

  public function getChangedFiles(?string $baseCommit = null, bool $includeUntracked = false): array
  {
    if (!$this->isGitRepository()) {
      return [];
    }

    $changedFiles = [];

    // If baseCommit is null, we're diffing against working tree
    if ($baseCommit === null) {
      // Get modified and staged files
      $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git diff --name-only --diff-filter=ACMRT 2>/dev/null';
      $output = [];
      @exec($command, $output);
      $changedFiles = array_merge($changedFiles, $output);

      // Get staged files
      $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git diff --cached --name-only --diff-filter=ACMRT 2>/dev/null';
      $output = [];
      @exec($command, $output);
      $changedFiles = array_merge($changedFiles, $output);

      // Include untracked files if requested
      if ($includeUntracked) {
        $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git ls-files --others --exclude-standard 2>/dev/null';
        $output = [];
        @exec($command, $output);
        $changedFiles = array_merge($changedFiles, $output);
      }
    } else {
      // Diff against specific commit
      $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git diff --name-only --diff-filter=ACMRT ' . escapeshellarg($baseCommit) . ' HEAD 2>/dev/null';
      $output = [];
      @exec($command, $output);
      $changedFiles = array_merge($changedFiles, $output);
    }

    // Remove duplicates
    $changedFiles = array_unique($changedFiles);
    $changedFiles = array_filter($changedFiles, fn($file) => $file !== '');

    // Filter out files that match .forgeignore patterns
    $filteredFiles = [];
    foreach ($changedFiles as $file) {
      $absolutePath = BASE_PATH . '/' . $file;

      // Check if file should be ignored
      if (!$this->ignoreService->shouldIgnore($absolutePath)) {
        $filteredFiles[] = $file;
      }
    }

    return array_values($filteredFiles);
  }

  public function getFirstCommitHash(): ?string
  {
    if (!$this->isGitRepository()) {
      return null;
    }

    $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git rev-list --max-parents=0 HEAD 2>/dev/null';
    $output = [];
    $exitCode = 0;
    @exec($command, $output, $exitCode);

    if ($exitCode !== 0 || empty($output)) {
      return null;
    }

    return trim($output[0]);
  }

  public function hasUncommittedChanges(): bool
  {
    if (!$this->isGitRepository()) {
      return false;
    }

    // Check for modified, staged, or untracked files
    $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git status --porcelain 2>/dev/null';
    $output = [];
    @exec($command, $output);

    if (empty($output)) {
      return false;
    }

    // Filter out ignored files
    $hasChanges = false;
    foreach ($output as $line) {
      $line = trim($line);
      if ($line === '') {
        continue;
      }

      // Parse git status line (format: XY filename)
      // X = index status, Y = working tree status
      if (preg_match('/^.{2}\s+(.+)$/', $line, $matches)) {
        $file = trim($matches[1]);
        $absolutePath = BASE_PATH . '/' . $file;

        // Only count files that are not ignored
        if (!$this->ignoreService->shouldIgnore($absolutePath)) {
          $hasChanges = true;
          break;
        }
      }
    }

    return $hasChanges;
  }

  public function getUncommittedFiles(): array
  {
    if (!$this->isGitRepository()) {
      return [];
    }

    $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git status --porcelain 2>/dev/null';
    $output = [];
    @exec($command, $output);

    $files = [];
    foreach ($output as $line) {
      $line = trim($line);
      if ($line === '') {
        continue;
      }

      // Parse git status line (format: XY filename)
      if (preg_match('/^.{2}\s+(.+)$/', $line, $matches)) {
        $file = trim($matches[1]);
        $absolutePath = BASE_PATH . '/' . $file;

        // Only include files that are not ignored
        if (!$this->ignoreService->shouldIgnore($absolutePath)) {
          $files[] = $file;
        }
      }
    }

    return array_unique($files);
  }

  public function getParentCommit(string $commitHash): ?string
  {
    if (!$this->isGitRepository()) {
      return null;
    }

    $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git rev-parse ' . escapeshellarg($commitHash . '^') . ' 2>/dev/null';
    $output = [];
    $exitCode = 0;
    @exec($command, $output, $exitCode);

    if ($exitCode !== 0 || empty($output)) {
      return null;
    }

    return trim($output[0]);
  }

  public function getFilesFromCommit(string $commitHash): array
  {
    if (!$this->isGitRepository()) {
      return [];
    }

    // Get all files that exist in the specified commit
    $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git ls-tree -r --name-only ' . escapeshellarg($commitHash) . ' 2>/dev/null';
    $output = [];
    @exec($command, $output);

    $files = [];
    foreach ($output as $file) {
      $file = trim($file);
      if ($file === '') {
        continue;
      }

      $absolutePath = BASE_PATH . '/' . $file;

      // Only include files that are not ignored
      if (!$this->ignoreService->shouldIgnore($absolutePath)) {
        $files[] = $file;
      }
    }

    return array_values($files);
  }

  public function getCommitMessage(string $commitHash): ?string
  {
    if (!$this->isGitRepository()) {
      return null;
    }

    $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git log -1 --format=%s ' . escapeshellarg($commitHash) . ' 2>/dev/null';
    $output = [];
    $exitCode = 0;
    @exec($command, $output, $exitCode);

    if ($exitCode !== 0 || empty($output)) {
      return null;
    }

    return trim(implode(' ', $output));
  }

  public function getChangedFilesBetweenCommits(string $fromCommit, string $toCommit): array
  {
    if (!$this->isGitRepository()) {
      return [];
    }

    // Get files changed between two specific commits
    $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git diff --name-only --diff-filter=ACMRT ' . escapeshellarg($fromCommit) . ' ' . escapeshellarg($toCommit) . ' 2>/dev/null';
    $output = [];
    @exec($command, $output);

    // Remove duplicates
    $changedFiles = array_unique($output);
    $changedFiles = array_filter($changedFiles, fn($file) => $file !== '');

    // Filter out files that match .forgeignore patterns
    $filteredFiles = [];
    foreach ($changedFiles as $file) {
      $absolutePath = BASE_PATH . '/' . $file;

      // Check if file should be ignored
      if (!$this->ignoreService->shouldIgnore($absolutePath)) {
        $filteredFiles[] = $file;
      }
    }

    return array_values($filteredFiles);
  }
}
