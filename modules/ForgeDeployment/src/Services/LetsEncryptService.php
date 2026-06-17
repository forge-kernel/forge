<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class LetsEncryptService
{
  public function __construct(
    private readonly SshService $sshService
  ) {
  }

  public function connect(string $host, int $port, string $username, ?string $privateKeyPath = null, ?string $publicKeyPath = null, ?string $passphrase = null): bool
  {
    return $this->sshService->connect($host, $port, $username, $privateKeyPath, $publicKeyPath, $passphrase);
  }

  public function setupSsl(string $domain, string $email, ?callable $outputCallback = null, ?callable $errorCallback = null): bool
  {
    $this->installCertbot($outputCallback, $errorCallback);
    $this->generateCertificate($domain, $email, $outputCallback, $errorCallback);
    $this->configureAutoRenewal($outputCallback, $errorCallback);

    return true;
  }

  public function updateNginxConfig(string $domain, string $rootPath, string $phpVersion, ?callable $outputCallback = null): bool
  {
    $config = $this->generateSslConfig($domain, $rootPath, $phpVersion);
    $configPath = "/etc/nginx/sites-available/{$domain}";

    // Remove old config to ensure clean overwrite
    $this->sshService->execute("rm -f {$configPath}", $outputCallback, null, 10);

    $this->sshService->uploadString($config, $configPath, $outputCallback);

    $result = $this->sshService->execute('nginx -t', $outputCallback, null, 60);
    if (!$result['success']) {
      $errorMsg = $result['error'] ?? $result['output'] ?? '';

      // Check for missing shared memory zones (old rate limiting configs)
      if (strpos($errorMsg, 'zero size shared memory zone') !== false || strpos($errorMsg, 'shared memory zone') !== false) {
        if ($outputCallback !== null) {
          $outputCallback('      Detected old rate limiting configs. Cleaning up...');
        }
        // Remove rate limiting directives from all site configs
        $this->sshService->execute('find /etc/nginx/sites-available -type f -exec sed -i "/limit_req/d" {} \\;', $outputCallback, null, 30);
        // Retry test
        $result = $this->sshService->execute('nginx -t', $outputCallback, null, 60);
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
          $this->sshService->execute("rm -f /etc/nginx/sites-enabled/{$brokenSite}", $outputCallback, null, 10);
          // Retry test
          $result = $this->sshService->execute('nginx -t', $outputCallback, null, 60);
          if ($result['success']) {
            if ($outputCallback !== null) {
              $outputCallback("      Broken site {$brokenSite} disabled. New site config validated.");
            }
          }
        }
      }

      if (!$result['success']) {
        throw new \RuntimeException('Nginx configuration test failed after SSL update: ' . ($result['error'] ?? 'Unknown error'));
      }
    }

    $result = $this->sshService->execute('systemctl reload nginx', $outputCallback, null, 60);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to reload Nginx after SSL update: ' . ($result['error'] ?? 'Unknown error'));
    }

    return true;
  }

  private function installCertbot(?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $checkResult = $this->sshService->execute('which certbot', $outputCallback, $errorCallback, 10);
    if ($checkResult['success'] && trim($checkResult['output'] ?? '') !== '') {
      if ($outputCallback !== null) {
        $outputCallback('      Certbot is already installed, skipping installation...');
      }
      return;
    }

    if ($outputCallback !== null) {
      $outputCallback('      Installing certbot...');
    }
    $result = $this->sshService->execute('apt-get install -y certbot python3-certbot-nginx', $outputCallback, $errorCallback, 600);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to install certbot: ' . $result['error']);
    }
  }

  private function generateCertificate(string $domain, string $email, ?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    if ($outputCallback !== null) {
      $outputCallback("      Generating SSL certificate for {$domain}...");
      $outputCallback('      This may take a few minutes while Let\'s Encrypt validates the domain...');
    }
    $result = $this->sshService->execute("certbot --nginx -d {$domain} --non-interactive --agree-tos --email {$email} --redirect", $outputCallback, $errorCallback, 600);
    if (!$result['success']) {
      $error = $result['error'] ?? 'Unknown error';
      if (strpos($error, 'timeout') !== false || strpos($error, 'timed out') !== false) {
        throw new \RuntimeException("Certbot command timed out. DNS may not have propagated yet. Error: {$error}");
      }
      throw new \RuntimeException('Failed to generate SSL certificate: ' . $error);
    }
  }

  private function configureAutoRenewal(?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    if ($outputCallback !== null) {
      $outputCallback('      Configuring automatic certificate renewal...');
    }
    $result = $this->sshService->execute('systemctl enable certbot.timer', $outputCallback, $errorCallback, 60);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to enable certbot timer: ' . $result['error']);
    }
    $result = $this->sshService->execute('systemctl start certbot.timer', $outputCallback, $errorCallback, 60);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to start certbot timer: ' . $result['error']);
    }
  }

  private function generateSslConfig(string $domain, string $rootPath, string $phpVersion): string
  {
    $rootPathEscaped = str_replace('/', '\/', $rootPath);

    return <<<EOF
server {
    listen 80;
    listen [::]:80;
    server_name {$domain};
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name {$domain};

    ssl_certificate /etc/letsencrypt/live/{$domain}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/{$domain}/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    root {$rootPath}/public;
    index index.php index.html;

    access_log /var/log/nginx/{$domain}-access.log;
    error_log /var/log/nginx/{$domain}-error.log;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
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
}
