<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Sources;

final class SftpSource extends AbstractSource
{
    private string $host;
    private int $port;
    private string $username;
    private ?string $password = null;
    private ?string $keyPath = null;
    private ?string $keyPassphrase = null;
    private string $basePath;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->host = $config['host'] ?? '';
        $this->port = (int)($config['port'] ?? 22);
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? null;
        $this->keyPath = $config['key_path'] ?? null;
        $this->keyPassphrase = $config['key_passphrase'] ?? null;
        $this->basePath = $config['base_path'] ?? '/';
    }

    public function fetchManifest(string $path): ?array
    {
        $connection = $this->connect();
        if (!$connection) {
            return null;
        }

        $sftp = ssh2_sftp($connection);
        if (!$sftp) {
            return null;
        }

        $manifestPath = rtrim($this->basePath, '/') . '/' . ltrim($path, '/') . '/modules.json';
        $filePath = "ssh2.sftp://{$sftp}/{$manifestPath}";

        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
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

        $sftp = ssh2_sftp($connection);
        if (!$sftp) {
            return false;
        }

        $modulePath = ltrim($sourcePath, '/');
        
        if ($version === null) {
            $pathParts = explode('/', trim($sourcePath, '/'));
            $version = end($pathParts);
        }
        
        $zipName = $version . '.zip';
        $zipPath = rtrim($this->basePath, '/') . '/modules/' . $modulePath . '/' . $zipName;
        $remotePath = "ssh2.sftp://{$sftp}/{$zipPath}";

        if (!file_exists($remotePath)) {
            return false;
        }

        $zipContent = file_get_contents($remotePath);
        if ($zipContent === false) {
            return false;
        }

        if (!file_put_contents($destinationPath, $zipContent)) {
            return false;
        }

        return $this->calculateIntegrity($destinationPath);
    }

    public function fetchModulesJson(): ?array
    {
        $connection = $this->connect();
        if (!$connection) {
            return null;
        }

        $sftp = ssh2_sftp($connection);
        if (!$sftp) {
            return null;
        }

        $modulesJsonPath = rtrim($this->basePath, '/') . '/modules.json';
        $filePath = "ssh2.sftp://{$sftp}/{$modulesJsonPath}";

        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
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
        if (!$connection) {
            return false;
        }

        $sftp = ssh2_sftp($connection);
        return $sftp !== false;
    }

    private function connect()
    {
        if (!function_exists('ssh2_connect')) {
            $this->error('SSH2 extension is not available. Install php-ssh2 extension.');
            return null;
        }

        $connection = @ssh2_connect($this->host, $this->port);
        if (!$connection) {
            return null;
        }

        $authenticated = false;

        if ($this->keyPath && file_exists($this->keyPath)) {
            $publicKey = $this->keyPath . '.pub';
            if (file_exists($publicKey)) {
                $authenticated = @ssh2_auth_pubkey_file(
                    $connection,
                    $this->username,
                    $publicKey,
                    $this->keyPath,
                    $this->keyPassphrase ?? ''
                );
            }
        }

        if (!$authenticated && $this->password) {
            $authenticated = @ssh2_auth_password($connection, $this->username, $this->password);
        }

        if (!$authenticated) {
            return null;
        }

        return $connection;
    }
}

