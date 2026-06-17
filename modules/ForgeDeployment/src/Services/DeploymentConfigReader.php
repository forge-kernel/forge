<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\FileExistenceCache;

#[Service]
final class DeploymentConfigReader
{
  private const CONFIG_FILE = 'forge-deployment.php';
  private const CONFIG_FILE_ALT = 'deployment.php';

  public function readConfig(?string $configPath = null): ?array
  {
    $pathsToCheck = [];
    if ($configPath !== null) {
      $pathsToCheck[] = $configPath;
    }
    
    $projectRoot = BASE_PATH;
    $configFile = $projectRoot . '/' . self::CONFIG_FILE;
    $pathsToCheck[] = $configFile;
    
    $configFileAlt = $projectRoot . '/' . self::CONFIG_FILE_ALT;
    $pathsToCheck[] = $configFileAlt;
    
    if (!empty($pathsToCheck)) {
      FileExistenceCache::preload($pathsToCheck);
    }
    
    if ($configPath !== null && FileExistenceCache::exists($configPath)) {
      return $this->loadConfigFile($configPath);
    }

    if (FileExistenceCache::exists($configFile)) {
      return $this->loadConfigFile($configFile);
    }

    if (FileExistenceCache::exists($configFileAlt)) {
      return $this->loadConfigFile($configFileAlt);
    }

    return null;
  }

  public function hasConfig(?string $configPath = null): bool
  {
    if ($configPath !== null && FileExistenceCache::exists($configPath)) {
      return true;
    }

    $projectRoot = BASE_PATH;
    return file_exists($projectRoot . '/' . self::CONFIG_FILE) ||
      file_exists($projectRoot . '/' . self::CONFIG_FILE_ALT);
  }

  private function loadConfigFile(string $path): ?array
  {
    if (!FileExistenceCache::exists($path) || !is_readable($path)) {
      return null;
    }

    $config = require $path;
    if (!is_array($config)) {
      return null;
    }

    return $this->normalizeConfig($config);
  }

  private function normalizeConfig(array $config): array
  {
    $normalized = [
      'php_executable' => $config['php_executable'] ?? null,
      'server' => $config['server'] ?? [],
      'provision' => $config['provision'] ?? [],
      'deployment' => $config['deployment'] ?? [],
    ];

    return $normalized;
  }

  public function getServerConfig(array $config): ?array
  {
    return $config['server'] ?? null;
  }

  public function getProvisionConfig(array $config): ?array
  {
    return $config['provision'] ?? null;
  }

  public function getDeploymentConfig(array $config): ?array
  {
    return $config['deployment'] ?? null;
  }

  public function generateConfigTemplate(): string
  {
    return <<<'PHP'
<?php

declare(strict_types=1);

return [
    'server' => [
        'name' => 'my-app-server',
        'region' => 'nyc1',
        'size' => 's-1vcpu-1gb',
        'image' => 'ubuntu-22-04-x64',
        'ssh_key_path' => null,
    ],

    'provision' => [
        'php_version' => '8.4',
        'database_type' => 'mysql',
        'database_version' => '8.0',
        'database_name' => 'forge_app',
        'database_user' => 'forge_user',
        'database_password' => 'secret',
    ],

    'deployment' => [
        'domain' => 'domain.com',
        'ssl_email' => 'email@domain.com',
        'commands' => [],
        'post_deployment_commands' => [
            'cache:flush',
            'cache:warm',
            'db:migrate --type=all',
            'storage:link',
            'modules:forge-deployment:fix-permissions',
            'modules:forgewire:minify',
            'asset:link --type=module --module=forge-wire',
        ],
        'env_vars' => [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'true',
            'CENTRAL_DOMAIN' => 'domain.com',
            'FORGE_WIRE_STALE_THRESHOLD' => '300',
            'CORS_ALLOWED_ORIGINS' => [
            ],
            'IP_WHITE_LIST' => [
                '127.0.0.1',
                '::1'
            ],
        ],
    ],
];
PHP;
  }
}
