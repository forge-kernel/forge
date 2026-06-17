<?php

namespace App\Modules\ForgeStaticHtml;

use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\DI\Container;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Routing\Router;

class StaticGenerator
{
    private Router $router;
    private string $outputDir;
    private array $config;

    public function __construct(array $config)
    {
        Router::init(Container::getInstance());
        $this->router = Router::getInstance();
        $this->config = $config;
        $this->outputDir = BASE_PATH . '/' . $config['output_dir'];
    }

    public function generate(): void
    {
        if ($this->config['clean_build']) {
            $this->cleanOutputDir();
        }

        $this->generateRoutes();

        if ($this->config['copy_assets']) {
            $this->copyAssets();
        }
    }

    private function generateRoutes(): void
    {
        $routes = $this->router->getRoutes();
        $dynamicRoutesConfig = $this->config['dynamic_routes'] ?? [];

        foreach ($routes as $route) {
            if ($this->shouldGenerateRoute($route)) {
                $isDynamicRoute = false;

                foreach ($dynamicRoutesConfig as $routeName => $dynamicRouteConfig) {
                    $routePattern = $dynamicRouteConfig['route_pattern'] ?? null;
                    $strposResult = strpos($route['uri'], $routePattern);

                    if ($routePattern && $strposResult === 0) {
                        $this->generateDynamicRoutes($route, $routeName, $dynamicRouteConfig);
                        $isDynamicRoute = true;
                        break;
                    }
                }

                if (!$isDynamicRoute) {
                    $this->generateRouteOutput($route);
                }
            } else {
            }
        }
        echo "Route generation completed.\n";
    }

    private function generateDynamicRoutes(array $route, string $routeName, array $dynamicRouteConfig): void
    {
        if ($dynamicRouteConfig['data_source'] !== 'Database') {
            echo "Warning: Dynamic route '{$routeName}' misconfigured or Database data source not specified.\n";
            return;
        }

        try {
            $database = Container::getInstance()->get(Connection::class);
        } catch (\Throwable $e) {
            echo "Warning: Database module not available. Skipping dynamic route '{$routeName}' generation.\n";
            echo "  Ensure the Database module is installed and configured if you want to generate dynamic routes from the Database.\n";
            return;
        }

        $categoriesTable = $dynamicRouteConfig['options']['categories_table'];
        $sectionsTable = $dynamicRouteConfig['options']['sections_table'];
        $categorySlugColumn = $dynamicRouteConfig['options']['category_slug_column'];
        $sectionSlugColumn = $dynamicRouteConfig['options']['section_slug_column'];
        $sectionCategoryIdColumn = $dynamicRouteConfig['options']['section_category_id_column'];

        $categories = $database->table($categoriesTable)->get();


        foreach ($categories as $category) {
            $sections = $database->table($sectionsTable)
                ->where($sectionCategoryIdColumn, $category['id'])
                ->get();

            foreach ($sections as $section) {
                $uri = str_replace(
                    ['{category}', '{slug}'],
                    [$category[$categorySlugColumn], $section[$sectionSlugColumn]],
                    $dynamicRouteConfig['route_pattern']
                );

                if ($this->matchesIncludePatterns($uri)) {
                    $this->generateRouteOutput(['uri' => $uri, 'method' => 'GET']);
                }
            }
        }
    }


    private function shouldGenerateRoute(array $route): bool
    {
        $isGet = $route['method'] === 'GET';
        //$isStatic = $this->isStaticRoute($route);
        $matchesPatterns = $this->matchesIncludePatterns($route['uri']);

        return $isGet && $matchesPatterns;
    }

    private function matchesIncludePatterns(string $uri): bool
    {
        $patterns = $this->config['include_paths'] ?? ['/'];

        if (in_array('/', $patterns, true)) {
            return true;
        }

        foreach ($patterns as $pattern) {
            $normalizedPattern = rtrim($pattern, '*') . '*';
            if (fnmatch($normalizedPattern, $uri) || $uri === $pattern) {
                return true;
            }
        }

        return false;
    }

    private function isStaticRoute(array $route): bool
    {
        $isStatic = !preg_match('/\{.*?\}/', $route['uri']) && strpos($route['uri'], '/_') !== 0;
        return $isStatic;
    }

    private function generateRouteOutput(array $route): void
    {
        $request  = $this->createMockRequest($route['uri']);
        $returned = Router::getInstance()->dispatch($request);

        $response = $returned instanceof Response
            ? $returned
            : new Response((string)$returned, 200);

        if ($response->getStatusCode() === 200) {
            $html = $response->getContent();
            $filePath = $this->getOutputPath($route['uri']);
            $outputDir = dirname($filePath);

            if (!FileExistenceCache::isDir($outputDir) && !mkdir($outputDir, 0755, true)) {
                echo "  Error: Failed to create output directory: {$outputDir}\n";
                return;
            }
            file_put_contents($filePath, $html);
        } else {
            echo "  Warning: Non-200 status code ({$response->getStatusCode()}) for route: {$route['uri']}. Skipping HTML save.\n";
        }
    }

    private function createMockRequest(string $uri): Request
    {
        $baseUrl = parse_url($this->config['base_url']);
        $host = $baseUrl['host'] ?? 'localhost';
        $scheme = $baseUrl['scheme'] ?? 'http';
        $port = $baseUrl['port'] ?? ($scheme === 'https' ? 443 : 80);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['SERVER_NAME'] = $host;
        $_SERVER['SERVER_PORT'] = $port;
        $_SERVER['HTTPS'] = $scheme === 'https' ? 'on' : 'off';

        return Request::createFromGlobals();
    }

    private function getOutputPath(string $uri): string
    {
        $pathParts = explode('/', trim($uri, '/'));
        $filename = array_pop($pathParts) ?: 'index';
        $path = implode('/', $pathParts);

        if (empty($path)) {
            return "{$this->outputDir}/{$filename}/index.html";
        } else {
            return "{$this->outputDir}/{$path}/{$filename}/index.html";
        }
    }


    private function cleanOutputDir(): void
    {
        if (is_dir($this->outputDir)) {
            $this->deleteDirectory($this->outputDir);
        }
        mkdir($this->outputDir, 0755, true);
    }

    private function copyAssets(): void
    {
        foreach ($this->config['asset_dirs'] as $assetDir) {
            $source = BASE_PATH . '/' . $assetDir;
            $dest = $this->outputDir . '/' . basename($assetDir);

            if (is_dir($source)) {
                $this->copyDirectory($source, $dest);
            }
        }
    }

    private function copyDirectory(string $src, string $dst): void
    {
        $dir = opendir($src);
        @mkdir($dst, 0755);

        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $srcFile = "$src/$file";
                $destFile = "$dst/$file";

                if (is_dir($srcFile)) {
                    $this->copyDirectory($srcFile, $destFile);
                } else {
                    copy($srcFile, $destFile);
                }
            }
        }
        closedir($dir);
    }

    private function deleteDirectory(string $dir): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
