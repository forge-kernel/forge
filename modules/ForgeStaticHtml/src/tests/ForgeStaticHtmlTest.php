<?php

declare(strict_types=1);

namespace Modules\ForgeStaticHtml\tests;

use Forge\Core\DI\Container;
use Modules\ForgeRouter\Routing\Route;
use Modules\ForgeRouter\Routing\Router;
use Modules\ForgeStaticHtml\StaticGenerator;
use Modules\ForgeTesting\Attributes\AfterEach;
use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use ReflectionClass;

#[Group('static-html')]
final class ForgeStaticHtmlTest extends TestCase
{
    private string $tmpDir;

    #[BeforeEach]
    public function setup(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/forge_static_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);

        $containerReflection = new ReflectionClass(Container::class);
        $instanceProp = $containerReflection->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue(null, null);

        $container = Container::getInstance();

        $routerReflection = new ReflectionClass(Router::class);
        $routerInstanceProp = $routerReflection->getProperty('instance');
        $routerInstanceProp->setAccessible(true);
        $routerInstanceProp->setValue(null, null);

        $container->bind(StaticHtmlTestController::class, StaticHtmlTestController::class);

        $router = Router::init($container, []);
        $router->registerControllers(StaticHtmlTestController::class);
    }



    #[AfterEach]
    public function cleanup(): void
    {
        $this->deleteDir($this->tmpDir);

        $routerReflection = new ReflectionClass(Router::class);
        $routerInstanceProp = $routerReflection->getProperty('instance');
        $routerInstanceProp->setAccessible(true);
        $routerInstanceProp->setValue(null, null);
    }

    #[Test('generates index page at output root')]
    public function generates_index_page(): void
    {
        $generator = new StaticGenerator([
            'output_dir' => $this->tmpDir,
            'max_depth' => 0,
            'clean_build' => true,
            'copy_assets' => false,
            'asset_discovery' => false,
        ]);

        $generator->generate();

        $indexPath = $this->tmpDir . '/index.html';
        $this->assertFileExists($indexPath);
        $this->assertStringContainsString('Home', file_get_contents($indexPath));
    }

    #[Test('follows links up to max_depth')]
    public function follows_links(): void
    {
        $generator = new StaticGenerator([
            'output_dir' => $this->tmpDir,
            'max_depth' => 3,
            'clean_build' => true,
            'copy_assets' => false,
            'asset_discovery' => false,
        ]);

        $generator->generate();

        $this->assertFileExists($this->tmpDir . '/index.html');
        $this->assertFileExists($this->tmpDir . '/about/index.html');
        $this->assertFileExists($this->tmpDir . '/contact/index.html');
    }

    #[Test('respects max_depth of 0 (no crawl)')]
    public function respects_max_depth_zero(): void
    {
        $generator = new StaticGenerator([
            'output_dir' => $this->tmpDir,
            'max_depth' => 0,
            'clean_build' => true,
            'copy_assets' => false,
            'asset_discovery' => false,
        ]);

        $generator->generate();

        $this->assertFileExists($this->tmpDir . '/index.html');
        $this->assertFileDoesNotExist($this->tmpDir . '/about/index.html');
    }

    #[Test('respects exclude_paths config')]
    public function respects_exclude_paths(): void
    {
        $generator = new StaticGenerator([
            'output_dir' => $this->tmpDir,
            'max_depth' => 3,
            'clean_build' => true,
            'copy_assets' => false,
            'asset_discovery' => false,
            'exclude_paths' => ['/about'],
        ]);

        $generator->generate();

        $this->assertFileExists($this->tmpDir . '/index.html');
        $this->assertFileDoesNotExist($this->tmpDir . '/about/index.html');
        $this->assertFileExists($this->tmpDir . '/contact/index.html');
    }

    #[Test('cleans output directory before generation')]
    public function cleans_output_directory(): void
    {
        $staleFile = $this->tmpDir . '/stale.html';
        file_put_contents($staleFile, 'stale');

        $generator = new StaticGenerator([
            'output_dir' => $this->tmpDir,
            'max_depth' => 0,
            'clean_build' => true,
            'copy_assets' => false,
            'asset_discovery' => false,
        ]);

        $generator->generate();

        $this->assertFileExists($this->tmpDir . '/index.html');
        $this->assertFileDoesNotExist($staleFile);
    }

    private function deleteDir(string $dirPath): void
    {
        if (!is_dir($dirPath)) {
            return;
        }
        foreach (scandir($dirPath) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dirPath . '/' . $item;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dirPath);
    }
}

class StaticHtmlTestController
{
    #[Route('/', 'GET')]
    public function home(): string
    {
        return '<html><body><h1>Home</h1><a href="/about">About</a><a href="/contact">Contact</a></body></html>';
    }

    #[Route('/about', 'GET')]
    public function about(): string
    {
        return '<html><body><h1>About</h1><a href="/contact">Contact</a></body></html>';
    }

    #[Route('/contact', 'GET')]
    public function contact(): string
    {
        return '<html><body><h1>Contact</h1></body></html>';
    }
}
