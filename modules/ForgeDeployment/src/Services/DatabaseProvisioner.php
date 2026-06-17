<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class DatabaseProvisioner
{
  public function __construct(
    private readonly SshService $sshService
  ) {
  }

  public function provision(string $type, ?string $version = null, int $ramMb = 1024, ?callable $progressCallback = null, ?callable $outputCallback = null, ?callable $errorCallback = null): bool
  {
    $progress = function (string $message) use ($progressCallback) {
      if ($progressCallback !== null) {
        $progressCallback($message);
      }
    };

    if ($type === 'sqlite') {
      $progress("    ⏭ Skipping database installation (SQLite detected)");
      return true;
    }

    if ($type === 'mysql') {
      $progress("    • Installing MySQL " . ($version ?? '8.0') . "...");
      $result = $this->provisionMysql($version ?? '8.0', $ramMb, $outputCallback, $errorCallback);
      $progress("    • Securing MySQL installation...");
      return $result;
    } elseif ($type === 'postgresql') {
      $progress("    • Installing PostgreSQL " . ($version ?? '14') . "...");
      return $this->provisionPostgresql($version ?? '14', $ramMb, $outputCallback, $errorCallback);
    }

    return false;
  }

  public function createDatabase(string $type, string $database, string $username, string $password): bool
  {
    if ($type === 'sqlite') {
      return true;
    }

    if ($type === 'mysql') {
      return $this->createMysqlDatabase($database, $username, $password);
    } elseif ($type === 'postgresql') {
      return $this->createPostgresqlDatabase($database, $username, $password);
    }

    return false;
  }

  private function provisionMysql(string $version, int $ramMb, ?callable $outputCallback = null, ?callable $errorCallback = null): bool
  {
    $result = $this->sshService->execute('export DEBIAN_FRONTEND=noninteractive && apt-get install -y mysql-server', $outputCallback, $errorCallback);
    if (!$result['success']) {
      if (strpos($result['error'] ?? '', 'lock') !== false || strpos($result['output'] ?? '', 'lock') !== false) {
        if ($outputCallback !== null) {
          $outputCallback('      Apt lock detected, waiting...');
        }
        $this->sshService->execute('sleep 5');
        return $this->provisionMysql($version, $ramMb, $outputCallback, $errorCallback);
      }
      throw new \RuntimeException('Failed to install MySQL: ' . $result['error']);
    }

    $result = $this->sshService->execute('systemctl start mysql', $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to start MySQL: ' . $result['error']);
    }

    // systemctl enable should complete quickly, use shorter timeout
    $result = $this->sshService->execute('systemctl enable mysql', $outputCallback, $errorCallback, 30);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to enable MySQL: ' . $result['error']);
    }

    $bufferPoolSize = (int) ($ramMb * 0.75);
    $maxConnections = max(100, (int) ($ramMb / 10));

    $mycnf = <<<EOF
[mysqld]
innodb_buffer_pool_size = {$bufferPoolSize}M
max_connections = {$maxConnections}
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 7
max_binlog_size = 100M

[mysql]
safe-updates
EOF;

    $uploaded = $this->sshService->uploadString($mycnf, '/tmp/mysql_custom.cnf', $outputCallback);
    if (!$uploaded) {
      throw new \RuntimeException('Failed to upload MySQL configuration file to /tmp/mysql_custom.cnf');
    }

    $checkConfig = $this->sshService->execute('grep -q "innodb_buffer_pool_size" /etc/mysql/mysql.conf.d/mysqld.cnf && echo "exists" || echo "missing"', $outputCallback, $errorCallback, 30);
    if (trim($checkConfig['output'] ?? '') === 'missing') {
      $result = $this->sshService->execute('cat /tmp/mysql_custom.cnf >> /etc/mysql/mysql.conf.d/mysqld.cnf', $outputCallback, $errorCallback, 60);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to configure MySQL: ' . $result['error']);
      }
    } else {
      if ($outputCallback !== null) {
        $outputCallback('      MySQL already configured, skipping append...');
      }
    }

    $result = $this->sshService->execute('rm -f /tmp/mysql_custom.cnf', $outputCallback, $errorCallback, 30);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to clean up temp file: ' . $result['error']);
    }

    $result = $this->sshService->execute('systemctl restart mysql', $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to restart MySQL: ' . $result['error']);
    }

    $this->secureMysql($outputCallback, $errorCallback);

    return true;
  }

  private function secureMysql(?callable $outputCallback = null, ?callable $errorCallback = null): void
  {
    $mysqlCmd = 'mysql --no-defaults';

    $result = $this->sshService->execute("{$mysqlCmd} -e \"DELETE FROM mysql.user WHERE User='' AND Host!='localhost';\"", $outputCallback, $errorCallback);
    if (!$result['success']) {
      $errorLower = strtolower($result['error'] ?? '');
      if (strpos($errorLower, '1175') !== false || strpos($errorLower, 'safe update') !== false) {
        $result = $this->sshService->execute("{$mysqlCmd} -e \"DELETE FROM mysql.user WHERE User='' AND (Host='' OR Host='%');\"", $outputCallback, $errorCallback);
      }
      if (!$result['success'] && strpos(strtolower($result['error'] ?? ''), '1175') === false && strpos(strtolower($result['error'] ?? ''), 'safe update') === false) {
        throw new \RuntimeException('Failed to secure MySQL (remove anonymous users): ' . $result['error']);
      }
    }

    $result = $this->sshService->execute("{$mysqlCmd} -e \"DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');\"", $outputCallback, $errorCallback);
    if (!$result['success']) {
      $errorLower = strtolower($result['error'] ?? '');
      if (strpos($errorLower, '1175') === false && strpos($errorLower, 'safe update') === false) {
        throw new \RuntimeException('Failed to secure MySQL (remove remote root): ' . $result['error']);
      }
    }

    $result = $this->sshService->execute("{$mysqlCmd} -e \"DROP DATABASE IF EXISTS test;\"", $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to secure MySQL (drop test database): ' . $result['error']);
    }

    $result = $this->sshService->execute("{$mysqlCmd} -e \"DELETE FROM mysql.db WHERE Db='test' OR Db LIKE 'test\\_%';\"", $outputCallback, $errorCallback);
    if (!$result['success']) {
      $errorLower = strtolower($result['error'] ?? '');
      if (strpos($errorLower, '1175') === false && strpos($errorLower, 'safe update') === false) {
        throw new \RuntimeException('Failed to secure MySQL (remove test database permissions): ' . $result['error']);
      }
    }

    $result = $this->sshService->execute("{$mysqlCmd} -e \"FLUSH PRIVILEGES;\"", $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to flush MySQL privileges: ' . $result['error']);
    }
  }

  private function createMysqlDatabase(string $database, string $username, string $password): bool
  {
    $dbEscaped = escapeshellarg($database);
    $userEscaped = escapeshellarg($username);
    $passEscaped = escapeshellarg($password);

    $this->sshService->execute("mysql -e \"CREATE DATABASE IF NOT EXISTS {$dbEscaped};\"");
    $this->sshService->execute("mysql -e \"CREATE USER IF NOT EXISTS {$userEscaped}@'localhost' IDENTIFIED BY {$passEscaped};\"");
    $this->sshService->execute("mysql -e \"GRANT ALL PRIVILEGES ON {$dbEscaped}.* TO {$userEscaped}@'localhost';\"");
    $this->sshService->execute("mysql -e \"FLUSH PRIVILEGES;\"");

    return true;
  }

  private function provisionPostgresql(string $version, int $ramMb, ?callable $outputCallback = null, ?callable $errorCallback = null): bool
  {
    $result = $this->sshService->execute('apt-get install -y postgresql postgresql-contrib', $outputCallback, $errorCallback);
    if (!$result['success']) {
      if (strpos($result['error'] ?? '', 'lock') !== false || strpos($result['output'] ?? '', 'lock') !== false) {
        if ($outputCallback !== null) {
          $outputCallback('      Apt lock detected, waiting...');
        }
        $this->sshService->execute('sleep 5');
        return $this->provisionPostgresql($version, $ramMb, $outputCallback, $errorCallback);
      }
      throw new \RuntimeException('Failed to install PostgreSQL: ' . $result['error']);
    }

    $result = $this->sshService->execute('systemctl start postgresql', $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to start PostgreSQL: ' . $result['error']);
    }

    // systemctl enable should complete quickly, use shorter timeout
    $result = $this->sshService->execute('systemctl enable postgresql', $outputCallback, $errorCallback, 30);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to enable PostgreSQL: ' . $result['error']);
    }

    $sharedBuffers = (int) ($ramMb * 0.25);
    $effectiveCache = (int) ($ramMb * 0.75);
    $workMem = max(4, (int) ($ramMb / 32));
    $maintenanceWorkMem = max(64, (int) ($ramMb / 8));
    $maxConnections = max(100, (int) ($ramMb / 10));

    $pgHba = $this->sshService->execute('cat /etc/postgresql/' . $version . '/main/pg_hba.conf', $outputCallback, $errorCallback)['output'];
    if (strpos($pgHba, 'local   all             all                                     md5') === false) {
      $result = $this->sshService->execute("sed -i 's/local   all             all                                     peer/local   all             all                                     md5/' /etc/postgresql/{$version}/main/pg_hba.conf", $outputCallback, $errorCallback);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to configure PostgreSQL authentication: ' . $result['error']);
      }
    }

    $postgresqlConf = <<<EOF
shared_buffers = {$sharedBuffers}MB
effective_cache_size = {$effectiveCache}MB
work_mem = {$workMem}MB
maintenance_work_mem = {$maintenanceWorkMem}MB
max_connections = {$maxConnections}
log_min_duration_statement = 2000
log_line_prefix = '%t [%p]: [%l-1] user=%u,db=%d,app=%a,client=%h '
logging_collector = on
log_directory = 'log'
log_filename = 'postgresql-%Y-%m-%d_%H%M%S.log'
log_rotation_age = 1d
log_rotation_size = 100MB
EOF;

    $uploaded = $this->sshService->uploadString($postgresqlConf, '/tmp/postgresql_custom.conf', $outputCallback);
    if (!$uploaded) {
      throw new \RuntimeException('Failed to upload PostgreSQL configuration file to /tmp/postgresql_custom.conf');
    }

    $checkConfig = $this->sshService->execute("grep -q \"shared_buffers\" /etc/postgresql/{$version}/main/postgresql.conf && echo \"exists\" || echo \"missing\"", $outputCallback, $errorCallback, 30);
    if (trim($checkConfig['output'] ?? '') === 'missing') {
      $result = $this->sshService->execute("cat /tmp/postgresql_custom.conf >> /etc/postgresql/{$version}/main/postgresql.conf", $outputCallback, $errorCallback, 60);
      if (!$result['success']) {
        throw new \RuntimeException('Failed to configure PostgreSQL: ' . $result['error']);
      }
    } else {
      if ($outputCallback !== null) {
        $outputCallback('      PostgreSQL already configured, skipping append...');
      }
    }

    $result = $this->sshService->execute('rm -f /tmp/postgresql_custom.conf', $outputCallback, $errorCallback, 30);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to clean up temp file: ' . $result['error']);
    }

    $result = $this->sshService->execute("systemctl restart postgresql", $outputCallback, $errorCallback);
    if (!$result['success']) {
      throw new \RuntimeException('Failed to restart PostgreSQL: ' . $result['error']);
    }

    return true;
  }

  private function createPostgresqlDatabase(string $database, string $username, string $password): bool
  {
    $dbEscaped = escapeshellarg($database);
    $userEscaped = escapeshellarg($username);
    $passEscaped = escapeshellarg($password);

    $this->sshService->execute("sudo -u postgres psql -c \"CREATE USER {$userEscaped} WITH PASSWORD {$passEscaped};\"");
    $this->sshService->execute("sudo -u postgres psql -c \"CREATE DATABASE {$dbEscaped} OWNER {$userEscaped};\"");
    $this->sshService->execute("sudo -u postgres psql -c \"GRANT ALL PRIVILEGES ON DATABASE {$dbEscaped} TO {$userEscaped};\"");

    return true;
  }
}
