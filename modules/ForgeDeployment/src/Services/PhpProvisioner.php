<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class PhpProvisioner
{
  public function __construct(
    private readonly SshService $sshService
  ) {
  }

  public function provision(string $version, int $ramMb = 1024, ?callable $progressCallback = null, ?callable $outputCallback = null, ?callable $errorCallback = null): bool
  {
    $progress = function (string $message) use ($progressCallback) {
      if ($progressCallback !== null) {
        $progressCallback($message);
      }
    };

    $progress("    • Installing PHP {$version}...");
    $this->installPhp($version, $outputCallback, $errorCallback);
    $progress("    • Installing PHP extensions...");
    $this->installExtensions($version, $outputCallback, $errorCallback);
    $progress("    • Configuring php.ini for production...");
    $this->configurePhpIni($version, $ramMb, $outputCallback, $errorCallback);
    $progress("    • Configuring PHP-FPM pool...");
    $this->configurePhpFpm($version, $ramMb, $outputCallback, $errorCallback);

    return true;
  }

  private function installPhp(string $version, ?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $result = $this->sshService->execute('apt-get install -y software-properties-common', $outputCallback, $errorCallback);
    if (!$result['success']) {
      if (strpos($result['error'] ?? '', 'lock') !== false || strpos($result['output'] ?? '', 'lock') !== false) {
        if ($outputCallback !== null) {
          $outputCallback('      Apt lock detected, waiting...');
        }
        $this->sshService->execute('sleep 5');
        $this->installPhp($version, $outputCallback, $errorCallback);
        return;
      }
      throw new \RuntimeException('Failed to install software-properties-common: ' . $result['error']);
    }

    $result = $this->sshService->execute('add-apt-repository -y ppa:ondrej/php', $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to add PHP repository: ' . $result['error']);
    }

    $result = $this->sshService->execute('apt-get update', $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to update package lists: ' . $result['error']);
    }

    $result = $this->sshService->execute("apt-get install -y php{$version}-fpm php{$version}-cli php{$version}-common", $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException("Failed to install PHP {$version}: " . $result['error']);
    }
  }

  private function installExtensions(string $version, ?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $extensions = [
      'pdo',
      'pdo-mysql',
      'pdo-pgsql',
      'mbstring',
      'xml',
      'curl',
      'zip',
      'gd',
      'intl',
      'opcache',
      'sqlite3',
      'pcntl',
    ];

    foreach ($extensions as $ext) {
      $result = $this->sshService->execute("apt-get install -y php{$version}-{$ext}", $outputCallback, $errorCallback);
      if (!$result['success']) {
        if ($ext === 'pcntl') {
          $checkResult = $this->sshService->execute("php{$version} -m | grep -i pcntl", $outputCallback, $errorCallback);
          if ($checkResult['success'] && strpos($checkResult['output'] ?? '', 'pcntl') !== false) {
            continue;
          }
        }
        throw new \RuntimeException("Failed to install PHP extension {$ext}: " . $result['error']);
      }
    }
  }

  private function configurePhpIni(string $version, int $ramMb, ?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $memoryLimit = min(256, (int) ($ramMb * 0.25));
    $maxFiles = (int) ($ramMb / 4);

    $baseIni = <<<EOF
[PHP]
memory_limit = {$memoryLimit}M
max_execution_time = 30
max_input_time = 60
post_max_size = 50M
upload_max_filesize = 50M

[opcache]
opcache.enable=1
opcache.memory_consumption={$memoryLimit}
opcache.interned_strings_buffer=16
opcache.max_accelerated_files={$maxFiles}
opcache.revalidate_freq=2
opcache.fast_shutdown=1

[Security]
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

[Session]
session.cookie_httponly = 1
session.use_strict_mode = 1
EOF;

    $fpmIni = $baseIni . "\ndisable_functions = passthru,system,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source\n";
    $cliIni = $baseIni . "\ndisable_functions = \n";

    $uploaded = $this->sshService->uploadString($fpmIni, "/tmp/99-forge-fpm.ini", $outputCallback);
    if (!$uploaded) {
      throw new \RuntimeException('Failed to upload PHP FPM configuration file');
    }

    $result = $this->sshService->execute("mv /tmp/99-forge-fpm.ini /etc/php/{$version}/fpm/conf.d/99-forge.ini", $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to move PHP-FPM config: ' . $result['error']);
    }

    $uploaded = $this->sshService->uploadString($cliIni, "/tmp/99-forge-cli.ini", $outputCallback);
    if (!$uploaded) {
      throw new \RuntimeException('Failed to upload PHP CLI configuration file');
    }

    $result = $this->sshService->execute("mv /tmp/99-forge-cli.ini /etc/php/{$version}/cli/conf.d/99-forge.ini", $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to move PHP-CLI config: ' . $result['error']);
    }
  }

  private function configurePhpFpm(string $version, int $ramMb, ?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $maxChildren = max(5, (int) ($ramMb / 64));
    $startServers = max(2, (int) ($maxChildren * 0.1));
    $minSpareServers = max(1, (int) ($startServers * 0.5));
    $maxSpareServers = max(2, (int) ($startServers * 1.5));

    $poolConfig = <<<EOF
[www]
user = www-data
group = www-data
listen = /run/php/php{$version}-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = {$maxChildren}
pm.start_servers = {$startServers}
pm.min_spare_servers = {$minSpareServers}
pm.max_spare_servers = {$maxSpareServers}
pm.max_requests = 500
request_terminate_timeout = 30s
EOF;

    $uploaded = $this->sshService->uploadString($poolConfig, "/tmp/www.conf.custom", $outputCallback);
    if (!$uploaded) {
      throw new \RuntimeException('Failed to upload PHP-FPM pool configuration file');
    }

    $result = $this->sshService->execute("mv /tmp/www.conf.custom /etc/php/{$version}/fpm/pool.d/www.conf", $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to configure PHP-FPM pool: ' . $result['error']);
    }

    $result = $this->sshService->execute("systemctl enable php{$version}-fpm", $outputCallback, $errorCallback, 30);
    if (!$result['success']) {
      throw new \RuntimeException("Failed to enable PHP-FPM: " . $result['error']);
    }

    $result = $this->sshService->execute("systemctl restart php{$version}-fpm", $outputCallback, $errorCallback, 60);
    if (!$result['success']) {
      throw new \RuntimeException("Failed to restart PHP-FPM: " . $result['error']);
    }

    $this->sshService->execute("update-alternatives --set php /usr/bin/php{$version}", $outputCallback, $errorCallback);

    $statusResult = $this->sshService->execute("systemctl is-active php{$version}-fpm", $outputCallback, $errorCallback, 10);
    if (trim($statusResult['output'] ?? '') !== 'active') {
      throw new \RuntimeException("PHP-FPM is not running after restart. Status: " . ($statusResult['output'] ?? 'unknown'));
    }

    $phpVersionCheck = $this->sshService->execute("php{$version} -v", $outputCallback, $errorCallback, 10);
    if (!$phpVersionCheck['success']) {
      throw new \RuntimeException("PHP CLI is not working. Error: " . ($phpVersionCheck['error'] ?? 'unknown'));
    }
  }
}
