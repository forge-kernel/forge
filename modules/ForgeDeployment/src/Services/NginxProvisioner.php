<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class NginxProvisioner
{
  public function __construct(
    private readonly SshService $sshService
  ) {
  }

  public function provision(string $phpVersion, int $ramMb = 1024, ?callable $progressCallback = null, ?callable $outputCallback = null, ?callable $errorCallback = null): bool
  {
    $progress = function (string $message) use ($progressCallback) {
      if ($progressCallback !== null) {
        $progressCallback($message);
      }
    };

    $progress("    • Installing Nginx...");
    $this->installNginx($outputCallback, $errorCallback);

    // Remove default site to avoid interference
    $this->sshService->execute('rm -f /etc/nginx/sites-enabled/default', $outputCallback, $errorCallback, 10);

    $progress("    • Configuring Nginx for production...");
    $this->configureNginx($ramMb, $outputCallback, $errorCallback);
    $progress("    • Creating default site template...");
    $this->createSiteTemplate($phpVersion, $outputCallback, $errorCallback);

    return true;
  }

  public function createSiteConfig(string $domain, string $rootPath, string $phpVersion, ?callable $outputCallback = null, ?callable $errorCallback = null): bool
  {
    $config = $this->generateSiteConfig($domain, $rootPath, $phpVersion);
    $configPath = "/etc/nginx/sites-available/{$domain}";

    // Remove old config to ensure clean overwrite
    $this->sshService->execute("rm -f {$configPath}", $outputCallback, $errorCallback, 10);

    $uploaded = $this->sshService->uploadString($config, $configPath, $outputCallback);
    if (!$uploaded) {
      throw new \RuntimeException("Failed to upload Nginx site configuration to {$configPath}");
    }

    $result = $this->sshService->execute("rm -f /etc/nginx/sites-enabled/{$domain} && ln -sf {$configPath} /etc/nginx/sites-enabled/{$domain}", $outputCallback, $errorCallback, 30);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to enable site: ' . $result['error']);
    }

    $result = $this->sshService->execute('nginx -t', $outputCallback, $errorCallback, 60);
    if (!$result['success']) {
      $errorMsg = $result['error'] ?? $result['output'] ?? '';

      // Check for missing shared memory zones (old rate limiting configs)
      if (strpos($errorMsg, 'zero size shared memory zone') !== false || strpos($errorMsg, 'shared memory zone') !== false) {
        if ($outputCallback !== null) {
          $outputCallback('      Detected old rate limiting configs. Cleaning up...');
        }
        // Regenerate main config to ensure it's clean
        $this->regenerateMainConfig($outputCallback, $errorCallback);
        // Remove rate limiting directives from all site configs
        $this->sshService->execute('find /etc/nginx/sites-available -type f -exec sed -i "/limit_req/d" {} \\;', $outputCallback, $errorCallback, 30);
        // Retry test
        $result = $this->sshService->execute('nginx -t', $outputCallback, $errorCallback, 60);
        if ($result['success']) {
          if ($outputCallback !== null) {
            $outputCallback('      Rate limiting directives removed. Config validated.');
          }
        }
      }

      // Check if error is in a different site config file
      if (!$result['success'] && preg_match('/in \/etc\/nginx\/sites-enabled\/([^:]+):(\d+)/', $errorMsg, $matches)) {
        $brokenSite = $matches[1];
        if ($brokenSite !== $domain) {
          if ($outputCallback !== null) {
            $outputCallback("      Detected broken config in {$brokenSite}, temporarily disabling...");
          }
          $this->sshService->execute("rm -f /etc/nginx/sites-enabled/{$brokenSite}", $outputCallback, $errorCallback, 10);
          // Retry test
          $result = $this->sshService->execute('nginx -t', $outputCallback, $errorCallback, 60);
          if ($result['success']) {
            if ($outputCallback !== null) {
              $outputCallback("      Broken site {$brokenSite} disabled. New site config validated.");
            }
          }
        }
      }

      // If still failing, check main config
      if (!$result['success']) {
        $checkBackup = $this->sshService->execute('test -f /etc/nginx/nginx.conf.backup && echo "exists" || echo "missing"', $outputCallback, $errorCallback, 10);
        if (trim($checkBackup['output'] ?? '') === 'exists') {
          if ($outputCallback !== null) {
            $outputCallback('      Nginx config test failed, restoring main config from backup...');
          }
          $restoreResult = $this->sshService->execute('cp /etc/nginx/nginx.conf.backup /etc/nginx/nginx.conf', $outputCallback, $errorCallback, 30);
          if ($restoreResult['success']) {
            $retestResult = $this->sshService->execute('nginx -t', $outputCallback, $errorCallback, 60);
            if ($retestResult['success']) {
              if ($outputCallback !== null) {
                $outputCallback('      Main nginx.conf restored and validated. Retrying site config...');
              }
              $result = $this->sshService->execute('nginx -t', $outputCallback, $errorCallback, 60);
              if (!$result['success']) {
                throw new \RuntimeException('Nginx configuration test failed after restore: ' . $result['error']);
              }
            } else {
              throw new \RuntimeException('Nginx configuration test failed even after restoring backup: ' . $retestResult['error']);
            }
          } else {
            throw new \RuntimeException('Nginx configuration test failed and backup restore also failed: ' . $result['error']);
          }
        } else {
          if ($outputCallback !== null) {
            $outputCallback('      No backup found, regenerating main nginx.conf...');
          }
          $this->regenerateMainConfig($outputCallback, $errorCallback);
          $retestResult = $this->sshService->execute('nginx -t', $outputCallback, $errorCallback, 60);
          if ($retestResult['success']) {
            if ($outputCallback !== null) {
              $outputCallback('      Main nginx.conf regenerated and validated. Retrying site config...');
            }
            $result = $this->sshService->execute('nginx -t', $outputCallback, $errorCallback, 60);
            if (!$result['success']) {
              throw new \RuntimeException('Nginx configuration test failed after regeneration: ' . $result['error']);
            }
          } else {
            throw new \RuntimeException('Nginx configuration test failed even after regenerating config: ' . $retestResult['error']);
          }
        }
      }
    }

    $result = $this->sshService->execute('systemctl reload nginx', $outputCallback, $errorCallback, 30);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to reload Nginx: ' . $result['error']);
    }

    return true;
  }

  private function installNginx(?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $result = $this->sshService->execute('apt-get install -y nginx', $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to install Nginx: ' . $result['error']);
    }

    $result = $this->sshService->execute('systemctl start nginx', $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to start Nginx: ' . $result['error']);
    }

    // systemctl enable should complete quickly, use shorter timeout
    $result = $this->sshService->execute('systemctl enable nginx', $outputCallback, $errorCallback, 30);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to enable Nginx: ' . $result['error']);
    }
  }

  private function configureNginx(int $ramMb, ?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $workerProcesses = $this->getCpuCount($outputCallback, $errorCallback);
    $workerConnections = max(1024, $ramMb * 2);

    $nginxConf = <<<EOF
user www-data;
worker_processes {$workerProcesses};
pid /run/nginx.pid;

events {
    worker_connections {$workerConnections};
    use epoll;
    multi_accept on;
}

http {
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    keepalive_requests 100;
    types_hash_max_size 2048;
    server_tokens off;

    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;

    client_max_body_size 50M;
    client_body_buffer_size 128k;

    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
EOF;

    $uploaded = $this->sshService->uploadString($nginxConf, '/tmp/nginx_main.conf', $outputCallback);
    if (!$uploaded) {
      throw new \RuntimeException('Failed to upload Nginx configuration file to /tmp/nginx_main.conf');
    }

    $verifyResult = $this->sshService->execute('grep -q "worker_connections" /etc/nginx/nginx.conf && echo "exists" || echo "missing"', $outputCallback, $errorCallback, 10);
    $isAlreadyOptimized = trim($verifyResult['output'] ?? '') === 'exists';

    // Additional check: does it look like a site config?
    $headResult = $this->sshService->execute('head -n 5 /etc/nginx/nginx.conf', $outputCallback, $errorCallback, 10);
    $isCorrupted = strpos($headResult['output'] ?? '', 'server {') !== false;

    if ($isAlreadyOptimized && !$isCorrupted) {
      if ($outputCallback !== null) {
        $outputCallback('      Nginx already optimized, skipping update...');
      }
      $this->sshService->execute('rm -f /tmp/nginx_main.conf', $outputCallback, $errorCallback, 10);
      return;
    }

    if ($isCorrupted) {
      if ($outputCallback !== null) {
        $outputCallback('      Nginx main config appears corrupted (contains server block), overwriting...');
      }
    }

    $result = $this->sshService->execute('cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup', $outputCallback, $errorCallback, 30);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to backup Nginx config: ' . $result['error']);
    }

    $result = $this->sshService->execute('mv /tmp/nginx_main.conf /etc/nginx/nginx.conf', $outputCallback, $errorCallback, 30);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to update Nginx config: ' . $result['error']);
    }

    $result = $this->sshService->execute('nginx -t', $outputCallback, $errorCallback, 60);
    if (!$result['success']) {
      if ($outputCallback !== null) {
        $outputCallback('      Nginx config test failed, restoring from backup...');
      }
      $restoreResult = $this->sshService->execute('cp /etc/nginx/nginx.conf.backup /etc/nginx/nginx.conf', $outputCallback, $errorCallback, 30);
      if (!$restoreResult['success']) {
        throw new \RuntimeException('Nginx configuration test failed and backup restore also failed: ' . $result['error']);
      }
      throw new \RuntimeException('Nginx configuration test failed. Config restored from backup. Error: ' . $result['error']);
    }

    $result = $this->sshService->execute('systemctl reload nginx', $outputCallback, $errorCallback, 60);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to reload Nginx: ' . $result['error']);
    }
  }

  private function createSiteTemplate(string $phpVersion, ?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $defaultConfig = <<<EOF
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    return 444;
}
EOF;

    $uploaded = $this->sshService->uploadString($defaultConfig, '/etc/nginx/sites-available/default', $outputCallback);
    if (!$uploaded) {
      throw new \RuntimeException('Failed to upload default Nginx site configuration');
    }
  }

  private function generateSiteConfig(string $domain, string $rootPath, string $phpVersion): string
  {
    $rootPathEscaped = str_replace('/', '\/', $rootPath);

    return <<<EOF
server {
    listen 80;
    listen [::]:80;
    server_name {$domain};

    root {$rootPath}/public;
    index index.php index.html;

    access_log /var/log/nginx/{$domain}-access.log;
    error_log /var/log/nginx/{$domain}-error.log;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php{$phpVersion}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

}
EOF;
  }

  private function getCpuCount(?callable $outputCallback = null, ?callable $errorCallback = null): int
  {
    $result = $this->sshService->execute('nproc', $outputCallback, $errorCallback);
    $count = (int) trim($result['output'] ?? '1');
    return max(1, $count);
  }

  private function regenerateMainConfig(?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $ramMb = 1024;
    $workerProcesses = $this->getCpuCount($outputCallback, $errorCallback);
    $workerConnections = max(1024, $ramMb * 2);

    $nginxConf = <<<EOF
user www-data;
worker_processes {$workerProcesses};
pid /run/nginx.pid;

events {
    worker_connections {$workerConnections};
    use epoll;
    multi_accept on;
}

http {
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    keepalive_requests 100;
    types_hash_max_size 2048;
    server_tokens off;

    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;

    client_max_body_size 50M;
    client_body_buffer_size 128k;

    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
EOF;

    $uploaded = $this->sshService->uploadString($nginxConf, '/tmp/nginx_main.conf', $outputCallback);
    if (!$uploaded) {
      throw new \RuntimeException('Failed to upload regenerated Nginx configuration file');
    }

    $result = $this->sshService->execute('cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup', $outputCallback, $errorCallback, 30);
    if (!$result['success'] && $outputCallback !== null) {
      $outputCallback('      Warning: Could not backup existing config before regeneration');
    }

    $result = $this->sshService->execute('mv /tmp/nginx_main.conf /etc/nginx/nginx.conf', $outputCallback, $errorCallback, 30);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to update Nginx config during regeneration: ' . $result['error']);
    }
  }
}
