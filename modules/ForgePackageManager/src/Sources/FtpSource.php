<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Sources;

final class FtpSource extends AbstractSource
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $basePath;
    private bool $passive;
    private bool $ssl;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->host = $config['host'] ?? '';
        $this->port = (int)($config['port'] ?? 21);
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->basePath = $config['base_path'] ?? '/';
        $this->passive = $config['passive'] ?? true;
        $this->ssl = $config['ssl'] ?? false;
    }

    public function fetchManifest(string $path): ?array
    {
        $connection = $this->connect();
        if (!$connection) {
            return null;
        }

        $manifestPath = rtrim($this->basePath, '/') . '/' . ltrim($path, '/') . '/modules.json';
        $tempFile = sys_get_temp_dir() . '/forge_manifest_' . uniqid() . '.json';

        if (!@ftp_get($connection, $tempFile, $manifestPath, FTP_BINARY)) {
            ftp_close($connection);
            return null;
        }

        $content = file_get_contents($tempFile);
        unlink($tempFile);
        ftp_close($connection);

        if ($content === false) {
            return null;
        }

        $manifest = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !$this->validateManifest($manifest)) {
            return null;
        }

        return $manifest;
    }

    public function downloadModule(string $sourcePath, string $destinationPath, ?string $version = null): bool|string
    {
        $connection = $this->connect();
        if (!$connection) {
            return false;
        }

        $modulePath = ltrim($sourcePath, '/');
        
        if ($version === null) {
            $pathParts = explode('/', trim($sourcePath, '/'));
            $version = end($pathParts);
        }
        
        $zipName = $version . '.zip';
        $zipPath = rtrim($this->basePath, '/') . '/modules/' . $modulePath . '/' . $zipName;

        if (!@ftp_get($connection, $destinationPath, $zipPath, FTP_BINARY)) {
            ftp_close($connection);
            return false;
        }

        ftp_close($connection);

        return $this->calculateIntegrity($destinationPath);
    }

    public function fetchModulesJson(): ?array
    {
        $connection = $this->connect();
        if (!$connection) {
            return null;
        }

        $modulesJsonPath = rtrim($this->basePath, '/') . '/modules.json';
        $tempFile = sys_get_temp_dir() . '/forge_modules_' . uniqid() . '.json';

        if (!@ftp_get($connection, $tempFile, $modulesJsonPath, FTP_BINARY)) {
            ftp_close($connection);
            return null;
        }

        $content = file_get_contents($tempFile);
        unlink($tempFile);
        ftp_close($connection);

        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !$this->validateModulesJson($data)) {
            return null;
        }

        return $data;
    }

    public function supportsVersioning(): bool
    {
        return true;
    }

    public function validateConnection(): bool
    {
        $connection = $this->connect();
        if ($connection) {
            ftp_close($connection);
            return true;
        }
        return false;
    }

    private function connect()
    {
        if ($this->ssl) {
            if (!function_exists('ftp_ssl_connect')) {
                $this->error('FTPS is not available. Install OpenSSL extension.');
                return null;
            }
            $connection = @ftp_ssl_connect($this->host, $this->port, 30);
        } else {
            $connection = @ftp_connect($this->host, $this->port, 30);
        }

        if (!$connection) {
            return null;
        }

        if (!@ftp_login($connection, $this->username, $this->password)) {
            ftp_close($connection);
            return null;
        }

        if ($this->passive) {
            @ftp_pasv($connection, true);
        }

        return $connection;
    }
}

