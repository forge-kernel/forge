<?php

declare(strict_types=1);

namespace Modules\ForgeStaticHtml;

use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Container;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Router;

final class StaticGenerator
{
    private Router $router;
    private string $outputDir;
    private array $config;
    private array $generatedPages = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $outputDir = $config['output_dir'] ?? 'public/static';
        $this->outputDir = str_starts_with($outputDir, '/')
            ? rtrim($outputDir, '/')
            : BASE_PATH . '/' . rtrim($outputDir, '/');
        $this->router = Router::getInstance();
    }

    public function generate(): void
    {
        if ($this->config['clean_build'] ?? true) {
            $this->cleanOutputDir();
        }

        $seedUrls = $this->discoverSeedUrls();
        $this->crawlAndGenerate($seedUrls);

        if ($this->config['copy_assets'] ?? true) {
            foreach ($this->config['asset_dirs'] ?? [] as $dir) {
                $this->copyDirectory(
                    BASE_PATH . '/' . $dir,
                    $this->outputDir . '/' . basename($dir)
                );
            }
        }

        if ($this->config['asset_discovery'] ?? true) {
            $this->discoverAndCopyAssets();
        }
    }

    private function discoverSeedUrls(): array
    {
        $urls = ['/'];

        foreach ($this->config['dynamic_routes'] ?? [] as $pattern => $config) {
            if (($config['source'] ?? '') !== 'database') {
                continue;
            }

            $urls = array_merge($urls, $this->resolveDatabaseRoute($pattern, $config));
        }

        return array_values(array_unique($urls));
    }

    private function resolveDatabaseRoute(string $pattern, array $config): array
    {
        $db = $this->resolveDatabase();
        if ($db === null) {
            return [];
        }

        $query = $config['query'] ?? '';
        if ($query === '') {
            return [];
        }

        try {
            $rows = $db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }

        $urls = [];
        foreach ($rows as $row) {
            $url = $pattern;
            foreach ($row as $column => $value) {
                $url = str_replace('{' . $column . '}', (string) $value, $url);
            }
            $urls[] = $url;
        }

        return $urls;
    }

    private function resolveDatabase(): ?DatabaseConnectionInterface
    {
        try {
            return Container::getInstance()->get(DatabaseConnectionInterface::class);
        } catch (\Throwable) {
            return null;
        }
    }

    private function crawlAndGenerate(array $seedUrls): void
    {
        $visited = [];
        $queue = [];

        foreach ($seedUrls as $url) {
            $normalized = $this->normalizeUrl($url);
            if (!isset($visited[$normalized])) {
                $visited[$normalized] = 0;
                $queue[] = [$normalized, 0];
            }
        }

        while (!empty($queue)) {
            [$url, $depth] = array_shift($queue);

            $content = $this->fetchPage($url);
            if ($content === null) {
                continue;
            }

            $this->writePage($url, $content);

            $maxDepth = $this->config['max_depth'] ?? 3;
            if ($maxDepth < 0 || $depth < $maxDepth) {
                foreach ($this->extractLinks($content, $url) as $link) {
                    $normalized = $this->normalizeUrl($link);
                    if (!isset($visited[$normalized]) && !$this->shouldIgnore($normalized)) {
                        $visited[$normalized] = $depth + 1;
                        $queue[] = [$normalized, $depth + 1];
                    }
                }
            }
        }
    }

    private function fetchPage(string $url): ?string
    {
        $request = $this->createRequest($url);
        $result = $this->router->dispatch($request);

        $response = $result instanceof Response
            ? $result
            : new Response((string) $result, 200);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return $response->getContent();
    }

    private function createRequest(string $uri): Request
    {
        $parts = parse_url($this->config['base_url'] ?? 'http://localhost');
        $host = $parts['host'] ?? 'localhost';
        $scheme = $parts['scheme'] ?? 'http';

        return new Request(
            queryParams: [],
            postData: [],
            serverParams: [
                'REQUEST_URI' => $uri,
                'REQUEST_METHOD' => 'GET',
                'HTTP_HOST' => $host,
                'SERVER_NAME' => $host,
                'SERVER_PORT' => $scheme === 'https' ? 443 : 80,
            ],
            requestMethod: 'GET',
            cookies: [],
        );
    }

    private function writePage(string $url, string $html): void
    {
        $filePath = $this->getOutputPath($url);
        $dir = dirname($filePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filePath, $html);
        $this->generatedPages[$url] = $html;
    }

    private function getOutputPath(string $uri): string
    {
        $path = trim(parse_url($uri, PHP_URL_PATH), '/');

        if ($path === '') {
            return $this->outputDir . '/index.html';
        }

        return $this->outputDir . '/' . $path . '/index.html';
    }

    private function extractLinks(string $html, string $baseUrl): array
    {
        $links = [];

        if (!preg_match_all('/<a\s[^>]*href\s*=\s*"([^"]+)"/i', $html, $matches)) {
            return $links;
        }

        foreach ($matches[1] as $href) {
            $resolved = $this->resolveUrl($href, $baseUrl);
            if ($resolved !== null) {
                $links[] = $resolved;
            }
        }

        return array_unique($links);
    }

    private function resolveUrl(string $href, string $baseUrl): ?string
    {
        $trimmed = trim($href);

        if (
            $trimmed === '' ||
            str_starts_with($trimmed, '#') ||
            str_starts_with($trimmed, 'javascript:') ||
            str_starts_with($trimmed, 'mailto:') ||
            str_starts_with($trimmed, 'tel:')
        ) {
            return null;
        }

        if (parse_url($trimmed, PHP_URL_SCHEME) !== null) {
            $baseHost = parse_url($this->config['base_url'] ?? 'http://localhost', PHP_URL_HOST);
            $linkHost = parse_url($trimmed, PHP_URL_HOST);

            if ($linkHost !== null && $linkHost !== $baseHost) {
                return null;
            }

            return $trimmed;
        }

        if (str_starts_with($trimmed, '/')) {
            return $trimmed;
        }

        $basePath = rtrim(parse_url($baseUrl, PHP_URL_PATH) ?? '/', '/');
        return $basePath . '/' . $trimmed;
    }

    private function normalizeUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if ($path === false || $path === null || $path === '') {
            return '/';
        }

        $path = '/' . ltrim($path, '/');

        return $path;
    }

    private function shouldIgnore(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        if ($path === null || $path === false) {
            return true;
        }

        foreach ($this->config['exclude_paths'] ?? [] as $exclude) {
            if (str_starts_with($path, rtrim($exclude, '/'))) {
                return true;
            }
        }

        foreach ($this->config['include_paths'] ?? ['/'] as $include) {
            if (str_starts_with($path, rtrim($include, '/'))) {
                return false;
            }
        }

        return true;
    }

    private function discoverAndCopyAssets(): void
    {
        $copied = [];

        foreach ($this->generatedPages as $pageUrl => $html) {
            foreach ($this->extractAssetUrls($html) as $assetUrl) {
                $resolved = $this->resolveUrl($assetUrl, $pageUrl);
                if ($resolved === null) {
                    continue;
                }

                $normalized = $this->normalizeAssetUrl($resolved);
                if (isset($copied[$normalized])) {
                    continue;
                }

                if ($this->copyAssetToOutput($normalized)) {
                    $copied[$normalized] = true;
                }
            }
        }
    }

    private function extractAssetUrls(string $html): array
    {
        $urls = [];

        if (preg_match_all('/<link[^>]*href\s*=\s*"([^"]+)"/i', $html, $m)) {
            $urls = array_merge($urls, $m[1]);
        }

        if (preg_match_all('/<script[^>]*src\s*=\s*"([^"]+)"/i', $html, $m)) {
            $urls = array_merge($urls, $m[1]);
        }

        if (preg_match_all('/<img[^>]*src\s*=\s*"([^"]+)"/i', $html, $m)) {
            $urls = array_merge($urls, $m[1]);
        }

        if (preg_match_all('/<source[^>]*src\s*=\s*"([^"]+)"/i', $html, $m)) {
            $urls = array_merge($urls, $m[1]);
        }

        if (preg_match_all('/url\(\s*["\']?([^"\')\s]+)["\']?\s*\)/i', $html, $m)) {
            $urls = array_merge($urls, $m[1]);
        }

        return array_unique($urls);
    }

    private function normalizeAssetUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if ($path === false || $path === null) {
            return $url;
        }

        return $path;
    }

    private function copyAssetToOutput(string $assetPath): bool
    {
        $source = BASE_PATH . $assetPath;

        if (!file_exists($source) || is_dir($source)) {
            return false;
        }

        $relative = ltrim($assetPath, '/');
        $destination = $this->outputDir . '/' . $relative;
        $destDir = dirname($destination);

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        return copy($source, $destination);
    }

    private function cleanOutputDir(): void
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->outputDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
    }

    private function copyDirectory(string $source, string $dest): void
    {
        if (!is_dir($source)) {
            return;
        }

        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $dest . '/' . $iterator->getSubPathname();

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item->getRealPath(), $target);
            }
        }
    }
}
