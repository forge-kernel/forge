<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class SystemProvisioner
{
  public function __construct(
    private readonly SshService $sshService
  ) {
  }

  public function provision(int $ramMb = 1024, ?callable $progressCallback = null, ?callable $outputCallback = null, ?callable $errorCallback = null): bool
  {
    $progress = function (string $message) use ($progressCallback) {
      if ($progressCallback !== null) {
        $progressCallback($message);
      }
    };

    $progress("    • Updating system packages...");
    $this->updateSystem($outputCallback, $errorCallback);
    $progress("    • Configuring swap file ({$ramMb}MB)...");
    $this->configureSwap($ramMb, $outputCallback, $errorCallback);
    $progress("    • Setting timezone to UTC...");
    $this->configureTimezone($outputCallback, $errorCallback);
    $progress("    • Configuring automatic security updates...");
    $this->configureSecurityUpdates($outputCallback, $errorCallback);
    $progress("    • Optimizing kernel parameters...");
    $this->optimizeKernel($outputCallback, $errorCallback);
    $progress("    • Configuring firewall (UFW) - allowing SSH, HTTP, HTTPS...");
    $this->configureFirewall($outputCallback, $errorCallback);

    return true;
  }

  private function updateSystem(?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $this->waitForAptLock($outputCallback, $errorCallback);

    $result = $this->sshService->execute('export DEBIAN_FRONTEND=noninteractive && apt-get update', $outputCallback, $errorCallback, 300);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to update package lists: ' . $result['error']);
    }

    $this->waitForAptLock($outputCallback, $errorCallback);

    $result = $this->sshService->execute('export DEBIAN_FRONTEND=noninteractive && apt-get upgrade -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold"', $outputCallback, $errorCallback, 1800);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to upgrade packages: ' . $result['error']);
    }
  }

  public function waitForAptLock(?callable $outputCallback = null, ?callable $errorCallback = null, int $maxWait = 120): void
  {
    $waited = 0;
    $hasShownMessage = false;

    while ($waited < $maxWait) {
      $lockResult = $this->sshService->execute('lsof /var/lib/dpkg/lock-frontend 2>/dev/null | wc -l', $outputCallback, $errorCallback);
      $lockCount = (int) trim($lockResult['output'] ?? '1');

      $processResult = $this->sshService->execute('pgrep -x apt-get | wc -l', $outputCallback, $errorCallback);
      $processCount = (int) trim($processResult['output'] ?? '1');

      if ($lockCount === 0 && $processCount === 0) {
        if ($hasShownMessage && $outputCallback !== null) {
          $outputCallback('      Apt lock released, proceeding...');
        }
        return;
      }

      if (!$hasShownMessage && $outputCallback !== null) {
        $outputCallback('      Waiting for apt processes to finish (this may take a minute)...');
        $hasShownMessage = true;
      }

      sleep(3);
      $waited += 3;
    }

    throw new \RuntimeException('Timeout waiting for apt lock to be released after ' . $maxWait . ' seconds. Lock count: ' . ($lockCount ?? 'unknown') . ', Process count: ' . ($processCount ?? 'unknown'));
  }

  private function configureSwap(int $ramMb, ?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $swapExists = $this->sshService->execute('test -f /swapfile && echo "exists" || echo "missing"', $outputCallback, $errorCallback, 10);
    $swapFileExists = trim($swapExists['output'] ?? '') === 'exists';

    $swapStatus = $this->sshService->execute('swapon --show 2>/dev/null | grep -q /swapfile && echo "enabled" || echo "disabled"', $outputCallback, $errorCallback, 10);
    $swapEnabled = trim($swapStatus['output'] ?? '') === 'enabled';

    if (!$swapFileExists || !$swapEnabled) {
      if (!$swapFileExists) {
        $swapSize = $ramMb < 2048 ? $ramMb * 2 : $ramMb;

        $result = $this->sshService->execute("fallocate -l {$swapSize}M /swapfile", $outputCallback, $errorCallback, 30);
        if (!$result['success']) {
          if ($outputCallback !== null) {
            $outputCallback('      fallocate failed or not supported, falling back to dd (this will take longer)...');
          }
          $result = $this->sshService->execute("dd if=/dev/zero of=/swapfile bs=1M count={$swapSize} status=none", $outputCallback, $errorCallback, 300);
          if (!$result['success']) {
            throw new \RuntimeException('Failed to create swap file with dd: ' . $result['error']);
          }
        }

        $this->sshService->execute('chmod 600 /swapfile', $outputCallback, $errorCallback, 60);
        $this->sshService->execute('mkswap /swapfile', $outputCallback, $errorCallback, 60);
      }

      if (!$swapEnabled) {
        $this->sshService->execute('swapon /swapfile', $outputCallback, $errorCallback, 30);
      }
    } else {
      if ($outputCallback !== null) {
        $outputCallback('      Swap file already exists and is enabled, skipping creation...');
      }
    }

    $checkFstab = $this->sshService->execute('grep -q "/swapfile" /etc/fstab && echo "exists" || echo "missing"', $outputCallback, $errorCallback, 30);
    if (trim($checkFstab['output'] ?? '') === 'missing') {
      $result = $this->sshService->execute('echo "/swapfile none swap sw 0 0" | tee -a /etc/fstab', $outputCallback, $errorCallback, 30);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to add swap to fstab: ' . $result['error']);
      }
    }

    $this->sshService->execute('sysctl vm.swappiness=10', $outputCallback, $errorCallback, 30);

    $sysctlCheck = $this->sshService->execute('grep -q "vm.swappiness=10" /etc/sysctl.conf && echo "exists" || echo "missing"', $outputCallback, $errorCallback, 30);
    if (trim($sysctlCheck['output'] ?? '') === 'missing') {
      $this->sshService->execute('echo "vm.swappiness=10" >> /etc/sysctl.conf', $outputCallback, $errorCallback, 30);
    }
  }

  private function configureFirewall(?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $statusResult = $this->sshService->execute('ufw status | head -1', $outputCallback, $errorCallback, 30);
    $currentStatus = $statusResult['output'] ?? '';
    $isActive = strpos($currentStatus, 'Status: active') !== false;

    $rulesResult = $this->sshService->execute('ufw status numbered 2>/dev/null || echo ""', $outputCallback, $errorCallback, 30);
    $existingRules = $rulesResult['output'] ?? '';
    $hasSshRule = strpos($existingRules, '22/tcp') !== false;
    $hasHttpRule = strpos($existingRules, '80/tcp') !== false;
    $hasHttpsRule = strpos($existingRules, '443/tcp') !== false;

    if (!$hasSshRule || !$hasHttpRule || !$hasHttpsRule) {
      if ($isActive) {
        $result = $this->sshService->execute('ufw --force disable', $outputCallback, $errorCallback);
        if (!$result['success']) {
          throw new \RuntimeException('Failed to disable firewall: ' . $result['error']);
        }
      }

      $result = $this->sshService->execute('ufw --force reset', $outputCallback, $errorCallback);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to reset firewall: ' . $result['error']);
      }

      $result = $this->sshService->execute('ufw default deny incoming', $outputCallback, $errorCallback);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to set firewall default deny incoming: ' . $result['error']);
      }

      $result = $this->sshService->execute('ufw default allow outgoing', $outputCallback, $errorCallback);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to set firewall default allow outgoing: ' . $result['error']);
      }

      $result = $this->sshService->execute('ufw allow 22/tcp comment "SSH"', $outputCallback, $errorCallback);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to allow SSH port: ' . $result['error']);
      }

      $result = $this->sshService->execute('ufw allow 80/tcp comment "HTTP"', $outputCallback, $errorCallback);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to allow HTTP port: ' . $result['error']);
      }

      $result = $this->sshService->execute('ufw allow 443/tcp comment "HTTPS"', $outputCallback, $errorCallback);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to allow HTTPS port: ' . $result['error']);
      }
    } else {
      if ($outputCallback !== null) {
        $outputCallback('      Firewall rules already configured, skipping...');
      }
    }

    if (!$isActive) {
      $result = $this->sshService->execute('ufw --force enable', $outputCallback, $errorCallback);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to enable firewall: ' . $result['error']);
      }
      sleep(2);
    }

    $result = $this->sshService->execute('ufw status numbered | head -5', $outputCallback, $errorCallback);
    if (strpos($result['output'], '22/tcp') === false) {
      $this->sshService->execute('ufw --force disable', $outputCallback, $errorCallback);
      throw new \RuntimeException('Firewall configuration failed - SSH port 22 not properly configured');
    }
  }

  private function configureTimezone(?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $result = $this->sshService->execute('timedatectl set-timezone UTC', $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to set timezone: ' . $result['error']);
    }
  }

  private function configureSecurityUpdates(?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $result = $this->sshService->execute('apt-get install -y unattended-upgrades', $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to install unattended-upgrades: ' . $result['error']);
    }

    $unattendedResult = $this->sshService->execute('cat /etc/apt/apt.conf.d/50unattended-upgrades 2>/dev/null || echo ""', $outputCallback, $errorCallback, 30);
    $unattendedConf = $unattendedResult['output'] ?? '';
    if (strpos($unattendedConf, 'Unattended-Upgrade::Automatic-Reboot') === false) {
      $result = $this->sshService->execute('echo "Unattended-Upgrade::Automatic-Reboot \"false\";" >> /etc/apt/apt.conf.d/50unattended-upgrades', $outputCallback, $errorCallback);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to configure automatic reboot: ' . $result['error']);
      }
    }

    $autoUpgradesExists = $this->sshService->execute('test -f /etc/apt/apt.conf.d/20auto-upgrades && echo "exists" || echo "missing"', $outputCallback, $errorCallback);
    $autoUpgradesFileExists = trim($autoUpgradesExists['output'] ?? '') === 'exists';

    if (!$autoUpgradesFileExists) {
      $result = $this->sshService->execute('echo -e "APT::Periodic::Update-Package-Lists \"1\";\nAPT::Periodic::Unattended-Upgrade \"1\";" > /etc/apt/apt.conf.d/20auto-upgrades', $outputCallback, $errorCallback);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to configure auto-upgrades: ' . $result['error']);
      }
    } else {
      $autoUpgradesResult = $this->sshService->execute('cat /etc/apt/apt.conf.d/20auto-upgrades', $outputCallback, $errorCallback, 30);
      $autoUpgradesContent = $autoUpgradesResult['output'] ?? '';

      if (strpos($autoUpgradesContent, 'APT::Periodic::Update-Package-Lists') === false) {
        $result = $this->sshService->execute('echo "APT::Periodic::Update-Package-Lists \"1\";" >> /etc/apt/apt.conf.d/20auto-upgrades', $outputCallback, $errorCallback);
        if (!$result['success']) {
          throw new \RuntimeException('Failed to configure auto-upgrades: ' . $result['error']);
        }
      }

      if (strpos($autoUpgradesContent, 'APT::Periodic::Unattended-Upgrade') === false) {
        $result = $this->sshService->execute('echo "APT::Periodic::Unattended-Upgrade \"1\";" >> /etc/apt/apt.conf.d/20auto-upgrades', $outputCallback, $errorCallback);
        if (!$result['success']) {
          throw new \RuntimeException('Failed to configure unattended upgrades: ' . $result['error']);
        }
      }
    }
  }

  private function optimizeKernel(?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $sysctl = <<<'EOF'
net.core.rmem_max = 16777216
net.core.wmem_max = 16777216
net.ipv4.tcp_rmem = 4096 87380 16777216
net.ipv4.tcp_wmem = 4096 65536 16777216
net.ipv4.tcp_fin_timeout = 30
net.ipv4.tcp_keepalive_time = 300
net.ipv4.tcp_max_syn_backlog = 8192
net.ipv4.tcp_tw_reuse = 1
net.ipv4.ip_local_port_range = 1024 65535
EOF;

    $sysctlResult = $this->sshService->execute('cat /etc/sysctl.conf', $outputCallback, $errorCallback, 30);
    $sysctlConf = $sysctlResult['output'] ?? '';
    $hasOptimizations = strpos($sysctlConf, 'net.core.rmem_max = 16777216') !== false;

    if (!$hasOptimizations) {
      $this->sshService->uploadString($sysctl, '/tmp/sysctl_additions.conf', $outputCallback);

      $result = $this->sshService->execute('cat /tmp/sysctl_additions.conf >> /etc/sysctl.conf', $outputCallback, $errorCallback);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to append sysctl config: ' . $result['error']);
      }

      $result = $this->sshService->execute('rm /tmp/sysctl_additions.conf', $outputCallback, $errorCallback);
      if (!$result['success']) {
      }
    } else {
      if ($outputCallback !== null) {
        $outputCallback('      Kernel optimizations already configured, skipping...');
      }
    }

    $result = $this->sshService->execute('sysctl -p', $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to apply sysctl settings: ' . $result['error']);
    }
  }
}
