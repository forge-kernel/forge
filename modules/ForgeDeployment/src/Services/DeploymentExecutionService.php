<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use App\Modules\ForgeLogger\Services\ForgeLoggerService;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class DeploymentExecutionService
{
  private const LOG_DIR = 'storage/framework/deployments';
  private const MAX_LOG_SIZE = 10 * 1024 * 1024;

  public function __construct(
    private readonly DeploymentConfigReader $configReader,
    private readonly ForgeLoggerService $logger,
  ) {
  }

  public function executeDeployment(string $command, array $args = [], ?callable $outputCallback = null): array
  {
    $deploymentId = $this->generateDeploymentId($command);
    $logPath = $this->getLogPath($deploymentId);

    $logDir = $this->getLogDirectory();
    if (!is_dir($logDir)) {
      @mkdir($logDir, 0755, true);
    }

    $this->storeDeploymentLog($deploymentId, "=== Deployment started at " . date('Y-m-d H:i:s') . " ===\n");
    $this->storeDeploymentLog($deploymentId, "Command: {$command}\n\n");

    $phpExecutable = $this->getPhpExecutable();
    $this->storeDeploymentLog($deploymentId, "Using PHP executable: {$phpExecutable}\n");

    $phpVersion = $this->getPhpVersion($phpExecutable);
    $this->storeDeploymentLog($deploymentId, "PHP version: {$phpVersion}\n");

    $this->logger->debug('Deployment PHP selection', [
      'deployment_id' => $deploymentId,
      'php_executable' => $phpExecutable,
      'php_version' => $phpVersion,
      'php_binary' => defined('PHP_BINARY') ? PHP_BINARY : null,
    ]);

    $hasSsh2 = $this->hasSsh2Extension($phpExecutable);
    if (!$hasSsh2) {
      $this->storeDeploymentLog($deploymentId, "WARNING: PHP executable does not have SSH2 extension loaded. Deployment may fail.\n");
      $this->storeDeploymentLog($deploymentId, "Please select a PHP binary with SSH2 extension from the deployment settings.\n\n");
      $this->logger->debug('SSH2 extension not found', [
        'deployment_id' => $deploymentId,
        'php_executable' => $phpExecutable,
        'normalized_path' => $this->normalizePath($phpExecutable),
        'real_path' => @realpath($phpExecutable),
      ]);
    } else {
      $this->storeDeploymentLog($deploymentId, "PHP executable has SSH2 extension: ✓\n\n");
      $this->logger->debug('SSH2 extension found', [
        'deployment_id' => $deploymentId,
        'php_executable' => $phpExecutable,
        'normalized_path' => $this->normalizePath($phpExecutable),
        'real_path' => @realpath($phpExecutable),
      ]);
    }

    if ($phpExecutable === 'php') {
      $resolved = false;

      if (defined('PHP_BINARY') && PHP_BINARY && file_exists(PHP_BINARY) && is_executable(PHP_BINARY)) {
        if (!str_contains(strtolower(PHP_BINARY), 'fpm')) {
          $phpExecutable = PHP_BINARY;
          $resolved = true;
        }
      }

      if (!$resolved) {
        $envPhpBinary = $_ENV['PHP_BINARY'] ?? getenv('PHP_BINARY');
        if ($envPhpBinary && file_exists($envPhpBinary) && is_executable($envPhpBinary) && !str_contains(strtolower($envPhpBinary), 'fpm')) {
          $phpExecutable = $envPhpBinary;
          $resolved = true;
        }
      }

      if (!$resolved) {
        $whichOutput = [];
        $whichReturnCode = 0;
        @exec('which php 2>/dev/null', $whichOutput, $whichReturnCode);
        if ($whichReturnCode === 0 && !empty($whichOutput[0])) {
          $phpExecutable = trim($whichOutput[0]);
          $resolved = true;
        }
      }

      if (!$resolved) {
        $commonPaths = ['/usr/bin/php', '/usr/local/bin/php', '/opt/homebrew/bin/php'];
        foreach ($commonPaths as $path) {
          if (file_exists($path) && is_executable($path) && !str_contains($path, 'fpm')) {
            $phpExecutable = $path;
            $resolved = true;
            break;
          }
        }
      }

      if (!$resolved) {
        $error = 'Could not find PHP executable. Please set PHP_BINARY environment variable or ensure php is in PATH.';
        $this->storeDeploymentLog($deploymentId, "ERROR: {$error}\n");
        return [
          'success' => false,
          'deployment_id' => $deploymentId,
          'error' => $error,
          'output' => '',
        ];
      }
    }

    if (!file_exists($phpExecutable) || !is_executable($phpExecutable)) {
      $error = "PHP executable not found or not executable: {$phpExecutable}";
      $this->storeDeploymentLog($deploymentId, "ERROR: {$error}\n");
      return [
        'success' => false,
        'deployment_id' => $deploymentId,
        'error' => $error,
        'output' => '',
      ];
    }

    $forgePath = BASE_PATH . '/forge.php';

    $argsString = $this->buildArgsString($args);

    // Get PHP config file for Herd installations to ensure SSH2 is loaded
    $phpConfigFile = $this->getPhpConfigFile($phpExecutable);
    $phpConfigFlag = $phpConfigFile ? ' -c ' . escapeshellarg($phpConfigFile) : '';

    if (PHP_OS_FAMILY === 'Windows') {
      $fullCommand = sprintf(
        '(cd /d %s && start /B %s%s %s %s %s >> %s 2>&1) > nul 2>&1 &',
        escapeshellarg(BASE_PATH),
        escapeshellarg($phpExecutable),
        $phpConfigFlag,
        escapeshellarg($forgePath),
        escapeshellarg($command),
        $argsString,
        escapeshellarg($logPath)
      );
    } else {
      $fullCommand = sprintf(
        '(cd %s && nohup %s%s %s %s %s >> %s 2>&1) > /dev/null 2>&1 & echo $!',
        escapeshellarg(BASE_PATH),
        escapeshellarg($phpExecutable),
        $phpConfigFlag,
        escapeshellarg($forgePath),
        escapeshellarg($command),
        $argsString,
        escapeshellarg($logPath)
      );
    }

    $this->logger->debug('Executing deployment command', [
      'deployment_id' => $deploymentId,
      'php_executable' => $phpExecutable,
      'php_executable_real' => @realpath($phpExecutable),
      'php_config_file' => $phpConfigFile,
      'command' => $command,
      'full_command' => $fullCommand,
    ]);

    $output = [];
    $returnCode = 0;
    @exec($fullCommand, $output, $returnCode);

    $this->logger->debug('Deployment command execution result', [
      'deployment_id' => $deploymentId,
      'output' => $output,
      'return_code' => $returnCode,
    ]);

    $pid = null;
    if (!empty($output[0]) && is_numeric($output[0])) {
      $pid = (int) $output[0];
    }

    if ($returnCode !== 0 && $pid === null) {
      $error = 'Failed to start deployment process in background';
      if (!empty($output)) {
        $error .= ': ' . implode("\n", $output);
      }
      $this->storeDeploymentLog($deploymentId, "ERROR: {$error}\n");
      return [
        'success' => false,
        'deployment_id' => $deploymentId,
        'error' => $error,
        'output' => '',
      ];
    }

    return [
      'success' => true,
      'deployment_id' => $deploymentId,
      'exit_code' => null,
      'output' => 'Deployment started in background',
    ];
  }

  public function getPhpExecutable(): string
  {
    static $phpPath = null;
    static $lastConfigPath = null;

    $config = $this->configReader->readConfig();
    $currentConfigPath = $config !== null && isset($config['php_executable']) ? $config['php_executable'] : null;

    if ($phpPath !== null && $currentConfigPath === $lastConfigPath) {
      $this->logger->debug('Using cached PHP executable', [
        'php_path' => $phpPath,
        'config_path' => $currentConfigPath,
      ]);
      return $phpPath;
    }

    $lastConfigPath = $currentConfigPath;

    $config = $this->configReader->readConfig();
    if ($config !== null && isset($config['php_executable']) && !empty($config['php_executable'])) {
      $configPhp = $this->normalizePath($config['php_executable']);
      $this->logger->debug('Checking configured PHP executable', [
        'config_php' => $configPhp,
        'exists' => file_exists($configPhp),
        'executable' => file_exists($configPhp) ? is_executable($configPhp) : false,
        'is_fpm' => str_contains(strtolower($configPhp), 'fpm'),
      ]);
      if (file_exists($configPhp) && is_executable($configPhp)) {
        if (!str_contains(strtolower($configPhp), 'fpm')) {
          $phpPath = $configPhp;
          $this->logger->debug('Using configured PHP executable', [
            'php_path' => $phpPath,
            'real_path' => @realpath($phpPath),
          ]);
          return $phpPath;
        }
      }
    }

    $possiblePaths = [];
    $phpBinaryPath = null;

    if (defined('PHP_BINARY') && PHP_BINARY) {
      $phpBinary = $this->normalizePath(PHP_BINARY);
      $this->logger->debug('Checking PHP_BINARY', [
        'php_binary' => $phpBinary,
        'normalized' => $phpBinary,
        'real_path' => @realpath($phpBinary),
        'exists' => file_exists($phpBinary),
        'executable' => file_exists($phpBinary) ? is_executable($phpBinary) : false,
        'is_fpm' => str_contains(strtolower($phpBinary), 'fpm'),
      ]);
      if (!str_contains(strtolower($phpBinary), 'fpm') && file_exists($phpBinary) && is_executable($phpBinary)) {
        $phpBinaryPath = $phpBinary;
        array_unshift($possiblePaths, $phpBinary);
      }
    }

    $envPhpBinary = $_ENV['PHP_BINARY'] ?? getenv('PHP_BINARY');
    if ($envPhpBinary && file_exists($envPhpBinary) && !str_contains(strtolower($envPhpBinary), 'fpm')) {
      if (!in_array($envPhpBinary, $possiblePaths)) {
        $possiblePaths[] = $envPhpBinary;
      }
    }

    $whichOutput = [];
    $whichReturnCode = 0;
    @exec('which php 2>/dev/null', $whichOutput, $whichReturnCode);
    if ($whichReturnCode === 0 && !empty($whichOutput[0])) {
      $whichPath = trim($whichOutput[0]);
      if ($whichPath && !str_contains($whichPath, 'fpm') && file_exists($whichPath)) {
        if (!in_array($whichPath, $possiblePaths)) {
          $possiblePaths[] = $whichPath;
        }
      }
    }

    $commonPaths = ['/usr/bin/php', '/usr/local/bin/php', '/opt/homebrew/bin/php'];
    foreach ($commonPaths as $path) {
      if (file_exists($path) && is_executable($path) && !str_contains($path, 'fpm')) {
        if (!in_array($path, $possiblePaths)) {
          $possiblePaths[] = $path;
        }
      }
    }

    foreach ($possiblePaths as $path) {
      if ($path && file_exists($path) && is_executable($path)) {
        if (str_contains(strtolower($path), 'fpm')) {
          continue;
        }

        $testOutput = [];
        $testReturnCode = 0;
        @exec(escapeshellarg($path) . ' -v 2>/dev/null', $testOutput, $testReturnCode);
        $this->logger->debug('Testing PHP path for CLI and SSH2', [
          'path' => $path,
          'normalized_path' => $this->normalizePath($path),
          'real_path' => @realpath($path),
          'version_output' => $testOutput[0] ?? null,
          'return_code' => $testReturnCode,
          'is_cli' => $testReturnCode === 0 && !empty($testOutput[0]) && str_contains($testOutput[0], 'cli'),
        ]);
        if ($testReturnCode === 0 && !empty($testOutput[0])) {
          if (str_contains($testOutput[0], 'cli')) {
            $hasSsh2 = $this->hasSsh2Extension($path);
            $this->logger->debug('SSH2 check result', [
              'path' => $path,
              'normalized_path' => $this->normalizePath($path),
              'real_path' => @realpath($path),
              'has_ssh2' => $hasSsh2,
            ]);
            if ($hasSsh2) {
              $phpPath = $path;
              $this->logger->debug('Selected PHP executable with SSH2', [
                'php_path' => $phpPath,
                'real_path' => @realpath($phpPath),
              ]);
              return $phpPath;
            }
          }
        }
      }
    }

    foreach ($possiblePaths as $path) {
      if ($path && file_exists($path) && is_executable($path)) {
        if (str_contains(strtolower($path), 'fpm')) {
          continue;
        }
        if ($this->hasSsh2Extension($path)) {
          $phpPath = $path;
          return $phpPath;
        }
      }
    }

    $whichOutput = [];
    $whichReturnCode = 0;
    @exec('which php 2>/dev/null', $whichOutput, $whichReturnCode);
    if ($whichReturnCode === 0 && !empty($whichOutput[0])) {
      $whichPath = trim($whichOutput[0]);
      if ($whichPath && file_exists($whichPath) && is_executable($whichPath)) {
        if ($this->hasSsh2Extension($whichPath)) {
          $phpPath = $whichPath;
          return $phpPath;
        }
      }
    }

    if ($phpBinaryPath !== null && file_exists($phpBinaryPath) && is_executable($phpBinaryPath)) {
      if (!str_contains(strtolower($phpBinaryPath), 'fpm')) {
        $testOutput = [];
        $testReturnCode = 0;
        @exec(escapeshellarg($phpBinaryPath) . ' -v 2>/dev/null', $testOutput, $testReturnCode);
        if ($testReturnCode === 0 && !empty($testOutput[0])) {
          if (str_contains($testOutput[0], 'cli') || !str_contains($testOutput[0], 'fpm')) {
            error_log('DeploymentExecutionService: Using PHP_BINARY without SSH2 verification: ' . $phpBinaryPath);
            $phpPath = $phpBinaryPath;
            return $phpPath;
          }
        }
      }
    }

    foreach ($possiblePaths as $path) {
      if ($path && file_exists($path) && is_executable($path)) {
        if (str_contains(strtolower($path), 'fpm')) {
          continue;
        }
        error_log('DeploymentExecutionService: Using PHP executable without SSH2 verification: ' . $path);
        $phpPath = $path;
        return $phpPath;
      }
    }

    if ($whichReturnCode === 0 && !empty($whichOutput[0])) {
      $whichPath = trim($whichOutput[0]);
      if ($whichPath && file_exists($whichPath) && is_executable($whichPath)) {
        error_log('DeploymentExecutionService: Using PHP executable from "which php" without SSH2 verification: ' . $whichPath);
        $phpPath = $whichPath;
        return $phpPath;
      }
    }

    error_log('DeploymentExecutionService: Could not find PHP CLI executable, falling back to "php" command');
    $phpPath = 'php';
    return $phpPath;
  }

  private function hasSsh2Extension(string $phpPath): bool
  {
    $normalizedPath = $this->normalizePath($phpPath);

    $this->logger->debug('Checking SSH2 extension', [
      'php_path' => $phpPath,
      'normalized_path' => $normalizedPath,
      'real_path' => @realpath($normalizedPath),
      'exists' => file_exists($normalizedPath),
      'executable' => file_exists($normalizedPath) ? is_executable($normalizedPath) : false,
    ]);

    if (!file_exists($normalizedPath) || !is_executable($normalizedPath)) {
      $this->logger->debug('PHP path does not exist or is not executable', [
        'normalized_path' => $normalizedPath,
        'real_path' => @realpath($normalizedPath),
      ]);
      return false;
    }

    $currentPhpHasSsh2 = function_exists('ssh2_connect');
    $this->logger->debug('Current PHP SSH2 status', [
      'current_php_has_ssh2' => $currentPhpHasSsh2,
      'php_binary' => defined('PHP_BINARY') ? PHP_BINARY : null,
      'php_binary_real' => defined('PHP_BINARY') ? @realpath(PHP_BINARY) : null,
      'checking_path' => $normalizedPath,
      'checking_path_real' => @realpath($normalizedPath),
    ]);

    if ($currentPhpHasSsh2 && defined('PHP_BINARY') && PHP_BINARY) {
      $normalizedPhpBinary = $this->normalizePath(PHP_BINARY);
      if ($normalizedPath === $normalizedPhpBinary) {
        $this->logger->debug('Paths match, trusting current PHP SSH2', [
          'normalized_path' => $normalizedPath,
          'normalized_php_binary' => $normalizedPhpBinary,
        ]);
        return true;
      }
    }

    if ($currentPhpHasSsh2) {
      $realPathCurrent = @realpath(PHP_BINARY);
      $realPathCheck = @realpath($normalizedPath);
      $this->logger->debug('Comparing real paths', [
        'real_path_current' => $realPathCurrent,
        'real_path_check' => $realPathCheck,
        'match' => $realPathCurrent && $realPathCheck && $realPathCurrent === $realPathCheck,
      ]);
      if ($realPathCurrent && $realPathCheck && $realPathCurrent === $realPathCheck) {
        $this->logger->debug('Real paths match, trusting current PHP SSH2', [
          'real_path' => $realPathCurrent,
        ]);
        return true;
      }

      if ($realPathCurrent && $realPathCheck) {
        $realPathCurrentNormalized = $this->normalizePath($realPathCurrent);
        $realPathCheckNormalized = $this->normalizePath($realPathCheck);
        $this->logger->debug('Comparing normalized real paths', [
          'real_path_current_normalized' => $realPathCurrentNormalized,
          'real_path_check_normalized' => $realPathCheckNormalized,
          'match' => $realPathCurrentNormalized === $realPathCheckNormalized,
        ]);
        if ($realPathCurrentNormalized === $realPathCheckNormalized) {
          $this->logger->debug('Normalized real paths match, trusting current PHP SSH2', [
            'normalized_real_path' => $realPathCurrentNormalized,
          ]);
          return true;
        }
      }
    }

    $realPathCheck = @realpath($normalizedPath);
    $pathToCheck = $realPathCheck ?: $normalizedPath;

    if (!file_exists($pathToCheck) || !is_executable($pathToCheck)) {
      return false;
    }

    // Try to find the PHP configuration file for Herd installations
    $phpConfigFile = $this->getPhpConfigFile($pathToCheck);

    // Build command with explicit config file if found
    if ($phpConfigFile) {
      $command = escapeshellarg($pathToCheck) . ' -c ' . escapeshellarg($phpConfigFile) . ' -r "echo extension_loaded(\'ssh2\') ? \'1\' : \'0\';" 2>&1';
    } else {
      $command = escapeshellarg($pathToCheck) . ' -r "echo extension_loaded(\'ssh2\') ? \'1\' : \'0\';" 2>&1';
    }

    $output = [];
    $returnCode = 0;
    @exec($command, $output, $returnCode);

    $this->logger->debug('SSH2 detection method 1: extension_loaded', [
      'path' => $pathToCheck,
      'normalized_path' => $normalizedPath,
      'real_path' => @realpath($pathToCheck),
      'php_config_file' => $phpConfigFile,
      'command' => $command,
      'output' => $output,
      'output_string' => implode('', $output),
      'return_code' => $returnCode,
    ]);

    if ($returnCode === 0 && !empty($output)) {
      $result = trim(implode('', $output));
      $this->logger->debug('SSH2 detection method 1 result', [
        'path' => $pathToCheck,
        'result' => $result,
        'is_ssh2' => $result === '1',
      ]);
      if ($result === '1') {
        $this->logger->debug('SSH2 found via extension_loaded', [
          'path' => $pathToCheck,
          'result' => $result,
        ]);
        return true;
      }
    }

    // Build command with explicit config file if found
    if ($phpConfigFile) {
      $command2 = escapeshellarg($pathToCheck) . ' -c ' . escapeshellarg($phpConfigFile) . ' -m 2>&1';
    } else {
      $command2 = escapeshellarg($pathToCheck) . ' -m 2>&1';
    }

    $output2 = [];
    $returnCode2 = 0;
    @exec($command2, $output2, $returnCode2);

    $modulesOutput = implode("\n", $output2);
    $this->logger->debug('SSH2 detection method 2: php -m', [
      'path' => $pathToCheck,
      'normalized_path' => $normalizedPath,
      'real_path' => @realpath($pathToCheck),
      'php_config_file' => $phpConfigFile,
      'command' => $command2,
      'output_lines' => count($output2),
      'output_preview' => substr($modulesOutput, 0, 500),
      'return_code' => $returnCode2,
      'has_ssh2_in_output' => stripos($modulesOutput, 'ssh2') !== false,
    ]);

    if ($returnCode2 === 0 && !empty($output2)) {
      if (preg_match('/^ssh2$/mi', $modulesOutput)) {
        $this->logger->debug('SSH2 found via php -m', [
          'path' => $pathToCheck,
          'modules_output_length' => strlen($modulesOutput),
        ]);
        return true;
      }
    }

    $this->logger->debug('SSH2 not found after all checks', [
      'path' => $pathToCheck,
      'normalized_path' => $normalizedPath,
      'real_path' => @realpath($pathToCheck),
      'method1_result' => $returnCode === 0 && !empty($output) ? trim(implode('', $output)) : 'failed',
      'method2_has_ssh2' => $returnCode2 === 0 && stripos($modulesOutput, 'ssh2') !== false,
    ]);
    return false;
  }

  private function normalizePath(string $path): string
  {
    $normalized = str_replace('//', '/', $path);
    while (str_contains($normalized, '//')) {
      $normalized = str_replace('//', '/', $normalized);
    }
    return $normalized;
  }

  /**
   * Get PHP configuration file path for a given PHP executable
   * Returns null if not found or not a Herd installation
   */
  private function getPhpConfigFile(string $phpPath): ?string
  {
    $normalizedPath = $this->normalizePath($phpPath);
    $realPath = @realpath($normalizedPath) ?: $normalizedPath;

    // Check if it's a Herd installation
    if (str_contains($realPath, 'Herd')) {
      // Extract PHP version from path (e.g., php84 -> 84)
      if (preg_match('/php(\d+)/', $realPath, $matches)) {
        $version = $matches[1];
        $herdConfigDir = $_SERVER['HOME'] ?? getenv('HOME') ?? '';
        if ($herdConfigDir) {
          $possibleConfigFile = $herdConfigDir . '/Library/Application Support/Herd/config/php/' . $version . '/php.ini';
          if (file_exists($possibleConfigFile)) {
            return $possibleConfigFile;
          }
        }
      }
    }

    return null;
  }

  public function storeDeploymentLog(string $deploymentId, string $output): void
  {
    $logDir = $this->getLogDirectory();
    $logPath = $this->getLogPath($deploymentId);

    if (!is_dir($logDir)) {
      @mkdir($logDir, 0755, true);
    }

    if (file_exists($logPath) && filesize($logPath) > self::MAX_LOG_SIZE) {
      $this->rotateLog($logPath);
    }

    file_put_contents($logPath, $output, FILE_APPEND | LOCK_EX);
  }

  public function getDeploymentLog(string $deploymentId): ?string
  {
    $logPath = $this->getLogPath($deploymentId);

    if (!file_exists($logPath)) {
      return null;
    }

    $content = file_get_contents($logPath);
    if ($content === false) {
      return null;
    }

    return $this->stripAnsiCodes($content);
  }

  private function stripAnsiCodes(string $text): string
  {
    return preg_replace('/\x1b\[[0-9;]*m/', '', $text);
  }

  public function deleteDeploymentLog(string $deploymentId): bool
  {
    $logPath = $this->getLogPath($deploymentId);

    if (file_exists($logPath)) {
      return unlink($logPath);
    }

    return true;
  }

  private function generateDeploymentId(string $command): string
  {
    $timestamp = time();
    $commandHash = substr(md5($command), 0, 8);
    return "deploy-{$timestamp}-{$commandHash}";
  }

  private function buildArgsString(array $args): string
  {
    if (empty($args)) {
      return '';
    }

    $parts = [];
    foreach ($args as $key => $value) {
      if (is_bool($value)) {
        if ($value) {
          $parts[] = "--{$key}";
        }
      } else {
        $parts[] = sprintf("--%s=%s", $key, escapeshellarg((string) $value));
      }
    }

    return implode(' ', $parts);
  }

  private function sanitizeOutput(string $output): string
  {
    $sensitivePatterns = [
      '/api[_-]?token["\']?\s*[:=]\s*["\']?([a-zA-Z0-9_-]{20,})/i',
      '/password["\']?\s*[:=]\s*["\']?([^\s"\']+)/i',
      '/secret["\']?\s*[:=]\s*["\']?([a-zA-Z0-9_-]{20,})/i',
    ];

    $sanitized = $output;
    foreach ($sensitivePatterns as $pattern) {
      $sanitized = preg_replace($pattern, '[REDACTED]', $sanitized);
    }

    return $sanitized;
  }

  private function getLogDirectory(): string
  {
    return BASE_PATH . '/' . self::LOG_DIR;
  }

  private function getLogPath(string $deploymentId): string
  {
    $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $deploymentId);
    return $this->getLogDirectory() . '/' . $safeId . '.log';
  }

  private function rotateLog(string $logPath): void
  {
    $backupPath = $logPath . '.old';
    if (file_exists($backupPath)) {
      @unlink($backupPath);
    }
    @rename($logPath, $backupPath);
  }

  public function getAvailablePhpBinaries(): array
  {
    $this->logger->debug('Starting PHP binaries detection', [
      'php_binary' => defined('PHP_BINARY') ? PHP_BINARY : null,
    ]);

    $binaries = [];
    $checkedPaths = [];

    if (defined('PHP_BINARY') && PHP_BINARY) {
      $phpBinary = $this->normalizePath(PHP_BINARY);
      $this->logger->debug('Checking PHP_BINARY for binaries list', [
        'php_binary' => PHP_BINARY,
        'normalized' => $phpBinary,
        'real_path' => @realpath($phpBinary),
        'exists' => file_exists($phpBinary),
        'executable' => file_exists($phpBinary) ? is_executable($phpBinary) : false,
        'is_fpm' => str_contains(strtolower($phpBinary), 'fpm'),
      ]);
      if (!str_contains(strtolower($phpBinary), 'fpm') && file_exists($phpBinary) && is_executable($phpBinary)) {
        $checkedPaths[] = $phpBinary;
      }
    }

    $envPhpBinary = $_ENV['PHP_BINARY'] ?? getenv('PHP_BINARY');
    if ($envPhpBinary) {
      $envPhpBinary = $this->normalizePath($envPhpBinary);
      if (file_exists($envPhpBinary) && is_executable($envPhpBinary) && !str_contains(strtolower($envPhpBinary), 'fpm')) {
        if (!in_array($envPhpBinary, $checkedPaths)) {
          $checkedPaths[] = $envPhpBinary;
        }
      }
    }

    $whichOutput = [];
    $whichReturnCode = 0;
    @exec('which php 2>/dev/null', $whichOutput, $whichReturnCode);
    if ($whichReturnCode === 0 && !empty($whichOutput[0])) {
      $whichPath = $this->normalizePath(trim($whichOutput[0]));
      if ($whichPath && !str_contains($whichPath, 'fpm') && file_exists($whichPath)) {
        if (!in_array($whichPath, $checkedPaths)) {
          $checkedPaths[] = $whichPath;
        }
      }
    }

    $commonDirs = ['/usr/bin', '/usr/local/bin', '/opt/homebrew/bin'];
    $homeDir = $_SERVER['HOME'] ?? getenv('HOME') ?? '';
    if ($homeDir) {
      $herdPath = $homeDir . '/Library/Application Support/Herd/bin';
      if (is_dir($herdPath)) {
        $commonDirs[] = $herdPath;
      }
    }

    foreach ($commonDirs as $dir) {
      if (!is_dir($dir)) {
        continue;
      }

      $files = @scandir($dir);
      if ($files === false) {
        continue;
      }

      foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
          continue;
        }

        if (str_starts_with($file, 'php') && !str_contains(strtolower($file), 'fpm')) {
          $path = $this->normalizePath($dir . '/' . $file);
          if (file_exists($path) && is_executable($path) && !in_array($path, $checkedPaths)) {
            $checkedPaths[] = $path;
          }
        }
      }
    }

    $phpInfo = $this->getPhpInfo();
    $currentPath = $phpInfo['path'] ?? null;
    $currentVersion = $phpInfo['version'] ?? PHP_VERSION;
    $currentHasSsh2 = function_exists('ssh2_connect');

    foreach ($checkedPaths as $path) {
      $normalizedPath = $this->normalizePath($path);
      if (!file_exists($normalizedPath) || !is_executable($normalizedPath)) {
        continue;
      }

      if (str_contains(strtolower($normalizedPath), 'fpm')) {
        continue;
      }

      $version = $normalizedPath === $currentPath ? $currentVersion : '';
      $hasSsh2 = $normalizedPath === $currentPath ? $currentHasSsh2 : false;

      $binaries[] = [
        'path' => $normalizedPath,
        'version' => $version,
        'has_ssh2' => $hasSsh2,
      ];
    }

    $this->logger->debug('PHP binaries detection complete', [
      'total_binaries' => count($binaries),
      'binaries_with_ssh2' => count(array_filter($binaries, fn($b) => $b['has_ssh2'])),
    ]);

    usort($binaries, function ($a, $b) {
      if ($a['has_ssh2'] !== $b['has_ssh2']) {
        return $b['has_ssh2'] ? 1 : -1;
      }
      if (empty($a['version']) || empty($b['version'])) {
        return 0;
      }
      return version_compare($b['version'], $a['version']);
    });

    return $binaries;
  }

  public function getPhpInfo(): array
  {
    static $phpInfo = null;

    if ($phpInfo !== null) {
      return $phpInfo;
    }

    $phpPath = $this->getPhpExecutable();
    $version = PHP_VERSION;
    $versionString = '';

    if ($phpPath !== 'php' && file_exists($phpPath)) {
      $versionOutput = [];
      @exec(escapeshellarg($phpPath) . ' -v 2>/dev/null', $versionOutput, $returnCode);
      if ($returnCode === 0 && !empty($versionOutput[0])) {
        if (preg_match('/PHP\s+([\d.]+)/', $versionOutput[0], $matches)) {
          $versionString = $matches[1];
        }
      }
    } else {
      $versionOutput = [];
      @exec('php -v 2>/dev/null', $versionOutput, $returnCode);
      if ($returnCode === 0 && !empty($versionOutput[0])) {
        if (preg_match('/PHP\s+([\d.]+)/', $versionOutput[0], $matches)) {
          $versionString = $matches[1];
        }
      }
    }

    $phpInfo = [
      'path' => $phpPath,
      'version' => $versionString ?: $version,
      'is_default' => $phpPath === 'php',
    ];

    $this->logger->debug('PHP info retrieved', [
      'php_info' => $phpInfo,
    ]);

    return $phpInfo;
  }

  private function getPhpVersion(string $phpPath): string
  {
    if ($phpPath === 'php') {
      return PHP_VERSION;
    }

    if (!file_exists($phpPath) || !is_executable($phpPath)) {
      return 'Unknown';
    }

    $versionOutput = [];
    @exec(escapeshellarg($phpPath) . ' -v 2>/dev/null', $versionOutput, $returnCode);
    if ($returnCode === 0 && !empty($versionOutput[0])) {
      if (preg_match('/PHP\s+([\d.]+)/', $versionOutput[0], $matches)) {
        return $matches[1];
      }
    }

    return PHP_VERSION;
  }
}
