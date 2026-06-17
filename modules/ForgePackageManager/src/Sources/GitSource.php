<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Sources;

final class GitSource extends AbstractSource
{
    private string $baseUrl;
    private string $branch;
    private string $rawBaseUrl;
    private bool $isPrivate;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->baseUrl = $config['url'] ?? '';
        $this->branch = $config['branch'] ?? 'main';
        $this->isPrivate = $config['private'] ?? false;
        $this->rawBaseUrl = $this->resolveRawBaseUrl();
    }

    private function fileGetContentsWithRetry(string $url): string|false
    {
        $context = $this->createContext(true);
        $content = @file_get_contents($url, false, $context);

        if ($content === false && $this->token && !$this->isPrivate) {
            $this->debug("Request failed with token on public repo. Retrying without token...");
            $context = $this->createContext(false);
            $content = @file_get_contents($url, false, $context);
        }

        return $content;
    }

    public function fetchManifest(string $path): ?array
    {
        $manifestUrl = $this->buildUrl($path);
        
        $content = $this->fileGetContentsWithRetry($manifestUrl);
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
        $zipUrl = $this->buildZipUrl($sourcePath, $version);
        
        $zipContent = $this->fileGetContentsWithRetry($zipUrl);
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
        $modulesJsonUrl = rtrim($this->rawBaseUrl, '/') . '/modules.json';
        $this->debug("Raw base URL: {$this->rawBaseUrl}");
        $this->debug("Fetching modules.json from: {$modulesJsonUrl}");
        
        $content = $this->fileGetContentsWithRetry($modulesJsonUrl);
        if ($content === false) {
            $error = error_get_last();
            $errorMsg = $error ? $error['message'] : 'Unknown error';
            $this->debug("Failed to fetch modules.json. Error: {$errorMsg}");
            return null;
        }

        $this->debug("Successfully fetched modules.json (" . strlen($content) . " bytes)");
        
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !$this->validateModulesJson($data)) {
            $jsonError = json_last_error_msg();
            $this->debug("Failed to parse modules.json. JSON error: {$jsonError}");
            return null;
        }

        $this->debug("Successfully parsed modules.json (" . count($data) . " modules found)");
        return $data;
    }

    public function supportsVersioning(): bool
    {
        return true;
    }

    public function validateConnection(): bool
    {
        $testUrl = rtrim($this->rawBaseUrl, '/') . '/modules.json';
        $context = $this->createContext(true);
        
        $headers = @get_headers($testUrl, false, $context);
        
        if ($headers === false && $this->token && !$this->isPrivate) {
             $context = $this->createContext(false);
             $headers = @get_headers($testUrl, false, $context);
        }

        if ($headers === false) {
            return false;
        }

        $statusCode = (int)substr($headers[0], 9, 3);
        return $statusCode >= 200 && $statusCode < 400;
    }

    private function resolveRawBaseUrl(): string
    {
        $url = $this->baseUrl;
        $branch = $this->branch;

        $this->debug("Resolving raw base URL from: {$url}");
        $this->debug("Using branch: {$branch}");

        if (preg_match('/^git@github\.com:(?<user>[^\/]+)\/(?<repo>[^\.]+).git$/', $url, $matches)) {
            $resolved = "https://raw.githubusercontent.com/{$matches['user']}/{$matches['repo']}/{$branch}";
            $this->debug("Matched GitHub SSH pattern, resolved to: {$resolved}");
            return $resolved;
        }

        if (preg_match('#^https?://github\.com/(?<user>[^/]+)/(?<repo>[^/]+)#i', $url, $matches)) {
            $resolved = "https://raw.githubusercontent.com/{$matches['user']}/{$matches['repo']}/{$branch}";
            $this->debug("Matched GitHub HTTPS pattern, resolved to: {$resolved}");
            return $resolved;
        }

        if (preg_match('#^https?://gitlab\.com/(?<user>[^/]+)/(?<repo>[^/]+)#i', $url, $matches)) {
            $resolved = "https://gitlab.com/{$matches['user']}/{$matches['repo']}/-/raw/{$branch}";
            $this->debug("Matched GitLab pattern, resolved to: {$resolved}");
            return $resolved;
        }

        if (preg_match('#^https?://bitbucket\.org/(?<user>[^/]+)/(?<repo>[^/]+)#i', $url, $matches)) {
            $resolved = "https://bitbucket.org/{$matches['user']}/{$matches['repo']}/raw/{$branch}";
            $this->debug("Matched Bitbucket pattern, resolved to: {$resolved}");
            return $resolved;
        }

        if (preg_match('#^https?://(?<host>[^/]+)/(?<user>[^/]+)/(?<repo>[^/]+)#i', $url, $matches)) {
            $host = $matches['host'];
            if (strpos($host, 'gitlab') !== false) {
                $resolved = "https://{$host}/{$matches['user']}/{$matches['repo']}/-/raw/{$branch}";
                $this->debug("Matched self-hosted GitLab pattern, resolved to: {$resolved}");
                return $resolved;
            }
            if (strpos($host, 'bitbucket') !== false) {
                $resolved = "https://{$host}/{$matches['user']}/{$matches['repo']}/raw/{$branch}";
                $this->debug("Matched self-hosted Bitbucket pattern, resolved to: {$resolved}");
                return $resolved;
            }
            if (strpos($host, 'dev.azure.com') !== false || strpos($host, 'azure.com') !== false) {
                $pathParts = explode('/', $matches['repo']);
                $project = $pathParts[0] ?? '';
                $repo = $pathParts[1] ?? $matches['repo'];
                $resolved = "https://dev.azure.com/{$matches['user']}/{$project}/_apis/git/repositories/{$repo}/items?path={$branch}&api-version=6.0";
                $this->debug("Matched Azure DevOps pattern, resolved to: {$resolved}");
                return $resolved;
            }
        }

        if (preg_match('#^https?://(?<host>[^/]+)/(?<path>.+)$#i', $url, $matches)) {
            $resolved = rtrim($url, '/') . '/' . $branch;
            $this->debug("Matched generic HTTPS pattern, resolved to: {$resolved}");
            return $resolved;
        }

        $resolved = rtrim($url, '/') . '/' . $branch;
        $this->debug("Using fallback resolution, resolved to: {$resolved}");
        return $resolved;
    }

    private function buildUrl(string $path): string
    {
        $base = rtrim($this->rawBaseUrl, '/');
        $path = ltrim($path, '/');
        return $base . '/' . $path;
    }

    private function buildZipUrl(string $sourcePath, ?string $version = null): string
    {
        $base = rtrim($this->rawBaseUrl, '/');
        $modulePath = 'modules/' . ltrim($sourcePath, '/');
        
        if ($version === null) {
            $pathParts = explode('/', trim($sourcePath, '/'));
            $version = end($pathParts);
        }
        
        $zipName = $version . '.zip';
        
        if (strpos($this->rawBaseUrl, 'raw.githubusercontent.com') !== false) {
            return $base . '/' . $modulePath . '/' . $zipName;
        }

        if (strpos($this->rawBaseUrl, 'gitlab.com') !== false || strpos($this->rawBaseUrl, '/-/raw/') !== false) {
            $base = str_replace('/-/raw/', '/-/archive/', $this->rawBaseUrl);
            return rtrim($base, '/') . '/' . $modulePath . '/' . $zipName;
        }

        return $base . '/' . $modulePath . '/' . $zipName;
    }

    private function createContext(bool $useToken = true)
    {
        // For public repositories, token is optional
        // For private repositories, token is required
        if ($this->isPrivate && !$this->token) {
            $this->warning("Private repository detected but no token provided. Access may fail.");
        }
        
        // GitHub requires a User-Agent header, otherwise returns 404
        $userAgent = "ForgePackageManager/1.0";
        $headers = ["User-Agent: {$userAgent}\r\n"];
        
        if ($useToken && $this->token) {
            if (strpos($this->rawBaseUrl, 'gitlab') !== false) {
                $headers[] = "PRIVATE-TOKEN: {$this->token}\r\n";
            } else {
                $headers[] = "Authorization: token {$this->token}\r\n";
            }
        }
        
        return stream_context_create([
            'http' => [
                'header' => implode('', $headers),
                'timeout' => 30,
                'follow_location' => 1,
                'max_redirects' => 5
            ]
        ]);
    }
}

