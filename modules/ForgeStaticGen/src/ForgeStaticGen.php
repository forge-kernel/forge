<?php

namespace App\Modules\ForgeStaticGen;

use App\Modules\ForgeMarkDown\Contracts\ForgeMarkDownInterface;
use App\Modules\ForgeMarkDown\ForgeMarkDown;
use App\Modules\ForgeStaticGen\Contracts\ForgeStaticGenInterface;
use Forge\CLI\Traits\OutputHelper;

class ForgeStaticGen implements ForgeStaticGenInterface
{
    use OutputHelper;

    private string $outputDir;

    public function __construct(private ForgeMarkDown $mdParser, string $outputDir = 'public/static')
    {
        $this->outputDir = $outputDir;
    }

    public function build(string $contentDir): void
    {
        $this->info("Static site generation: Starting...");
        $this->info("Content directory path: " . $contentDir);

        if (!is_dir($contentDir)) {
            $this->error("Error: Content directory not found at: " . $contentDir);
            return;
        }

        $this->info("Content directory exists.");

        $directory = new \RecursiveDirectoryIterator($contentDir);
        $iterator = new \RecursiveIteratorIterator($directory);

        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile() && $fileinfo->getExtension() === 'md') {
                $mdFile = $fileinfo->getPathname();
                $this->info("Processing MD file: " . $mdFile);

                $data = $this->mdParser->parseFile($mdFile);
                try {
                    $html = $this->applyLayout($data);
                } catch (\Throwable $layoutException) {
                    $this->error("Error during applyLayout() for: " . $mdFile);
                    $this->error("Exception: " . get_class($layoutException));
                    $this->error("Message: " . $layoutException->getMessage());
                    $this->error("Stack trace:\n" . $layoutException->getTraceAsString());
                    $html = '';
                }
                $this->saveHtml($mdFile, $html);
            }
        }

        $assetsDirSource = $contentDir . "assets";
        $assetsDirDest = $this->outputDir . '/assets';

        $this->copyAssets($assetsDirSource, $assetsDirDest);
    }

    private function applyLayout(array $data): string
    {
        $layout = $data['front_matter']['layout'] ?? 'default';
        $layoutBuilder = new LayoutBuilder();
        $html = $layoutBuilder->render($layout, [
            'content' => $data['content'],
            'meta' => $data['front_matter'],
            'renderer' => $layoutBuilder
        ]);
        return $html;
    }

    private function saveHtml($mdFile, $html)
    {
        $outputFilePath = $this->getHtmlFilePath($mdFile);
        $outputDirPath = dirname($outputFilePath);

        if (!is_dir($outputDirPath)) {
            mkdir($outputDirPath, 0755, true);
        }
        file_put_contents($outputFilePath, $html);
        echo "Generated: " . $outputFilePath . "\n";
    }

    private function getHtmlFilePath($mdFile): string
    {
        $baseName = basename($mdFile, '.md');
        $contentBasePath = BASE_PATH . "/docs/";
        $mdFileDir = dirname($mdFile);
        $contentBasePath = rtrim($contentBasePath, '/');
        $mdFileDir = rtrim($mdFileDir, '/');
        $relativePath = str_replace($contentBasePath, '', $mdFileDir);
        $relativePath = trim($relativePath, '/');
        $relativePath = str_replace('//', '/', $relativePath);

        return BASE_PATH . "/{$this->outputDir}/{$relativePath}/{$baseName}.html";
    }

    private function copyAssets($assetsDirSource, $assetsDirDest)
    {
        if (!is_dir($assetsDirSource)) {
            echo "Warning: Assets directory not found: {$assetsDirSource}\n";
            return;
        }

        if (!is_dir($assetsDirDest)) {
            mkdir($assetsDirDest, 0755, true);
        }

        $this->recursiveCopy($assetsDirSource, $assetsDirDest);
        echo "Assets copied from: {$assetsDirSource} to {$assetsDirDest}\n";
    }

    private function recursiveCopy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recursiveCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
