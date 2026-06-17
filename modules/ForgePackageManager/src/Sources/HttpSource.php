<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Sources;

final class HttpSource extends AbstractSource
{
    private string $baseUrl;
    private ?string $username = null;
    private ?string $password = null;
    private int $timeout;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->baseUrl = rtrim($config['base_url'] ?? '', '/');
        $this->username = $config['username'] ?? null;
        $this->password = $config['password'] ?? null;
        $this->timeout = (int)($config['timeout'] ?? 30);
    }

    public function fetchManifest(string $path): ?array
    {
        $manifestUrl = $this->buildUrl($path . '/modules.json');
        $context = $this->createContext();
        
        $content = @file_get_contents($manifestUrl, false, $context);
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
        $modulePath = ltrim($sourcePath, '/');
        
        if ($version === null) {
            $pathParts = explode('/', trim($sourcePath, '/'));
            $version = end($pathParts);
        }
        
        $zipName = $version . '.zip';
        $zipUrl = $this->buildUrl('modules/' . $modulePath . '/' . $zipName);
        $context = $this->createContext();
        
        $zipContent = @file_get_contents($zipUrl, false, $context);
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
        $modulesJsonUrl = $this->baseUrl . '/modules.json';
        $context = $this->createContext();
        
        $content = @file_get_contents($modulesJsonUrl, false, $context);
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
        $testUrl = $this->baseUrl . '/modules.json';
        $context = $this->createContext();
        
        $headers = @get_headers($testUrl, 1, $context);
        if ($headers === false) {
            return false;
        }

        $statusCode = (int)substr($headers[0], 9, 3);
        return $statusCode >= 200 && $statusCode < 400;
    }

    private function buildUrl(string $path): string
    {
        $base = rtrim($this->baseUrl, '/');
        $path = ltrim($path, '/');
        return $base . '/' . $path;
    }

    private function createContext()
    {
        $options = [
            'http' => [
                'timeout' => $this->timeout,
                'follow_location' => 1,
                'max_redirects' => 5,
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ];

        if ($this->username && $this->password) {
            $auth = base64_encode($this->username . ':' . $this->password);
            $options['http']['header'] = "Authorization: Basic {$auth}\r\n";
        }

        return stream_context_create($options);
    }
}

