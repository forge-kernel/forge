<?php

declare(strict_types=1);

namespace Modules\ForgeView\tests;

use Modules\ForgeTesting\Attributes\AfterEach;
use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Modules\ForgeView\View;
use Forge\Core\DI\Container;

#[Group('view')]
final class ViewTest extends TestCase
{
    private string $tmpDir;

    #[BeforeEach]
    public function setup(): void
    {
        Container::getInstance();
        $this->tmpDir = sys_get_temp_dir() . '/view_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
        mkdir($this->tmpDir . '/layouts', 0777, true);
        mkdir($this->tmpDir . '/components', 0777, true);
    }

    #[AfterEach]
    public function cleanup(): void
    {
        $this->deleteDir($this->tmpDir);
    }

    private function deleteDir(string $dirPath): void
    {
        if (!is_dir($dirPath))
            return;
        foreach (scandir($dirPath) as $item) {
            if ($item == '.' || $item == '..')
                continue;
            $path = $dirPath . '/' . $item;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dirPath);
    }

    #[Test('renders simple view file without layout')]
    public function renders_simple_view(): void
    {
        file_put_contents($this->tmpDir . '/hello.php', '<h1>Hello <?= $name ?></h1>');

        $view = new View(Container::getInstance(), $this->tmpDir, $this->tmpDir . '/components');
        $content = $view->render('hello', ['name' => 'Forge']);

        $this->assertEquals('<h1>Hello Forge</h1>', $content);
    }

    #[Test('renders view with layout and sections')]
    public function renders_with_layout_and_sections(): void
    {
        file_put_contents($this->tmpDir . '/layouts/app.php', '<html><head><title><?= \Modules\ForgeView\View::section("title") ?></title></head><body><?= $content ?></body></html>');
        file_put_contents($this->tmpDir . '/page.php', "<?php \Modules\ForgeView\View::layout('app'); \Modules\ForgeView\View::startSection('title'); ?>My Page<?php \Modules\ForgeView\View::endSection(); ?><h1>Page content</h1>");

        $view = new View(Container::getInstance(), $this->tmpDir, $this->tmpDir . '/components');
        $content = $view->render('page');

        $this->assertEquals('<html><head><title>My Page</title></head><body><h1>Page content</h1></body></html>', $content);
    }

    #[Test('suppressLayout ignores layout directive')]
    public function suppress_layout_ignores_directive(): void
    {
        file_put_contents($this->tmpDir . '/page2.php', "<?php \Modules\ForgeView\View::layout('app'); ?>only content");

        View::suppressLayout(true);
        $view = new View(Container::getInstance(), $this->tmpDir, $this->tmpDir . '/components');
        $content = $view->render('page2');

        $this->assertEquals('only content', $content);
    }

    #[Test('view variables are extracted and isolated')]
    public function variables_extracted(): void
    {
        file_put_contents($this->tmpDir . '/vars.php', '<?= $a ?> + <?= $b ?> = <?= $a + $b ?>');

        $view = new View(Container::getInstance(), $this->tmpDir, $this->tmpDir . '/components');
        $content = $view->render('vars', ['a' => 2, 'b' => 3]);

        $this->assertEquals('2 + 3 = 5', $content);
    }

    #[Test('throws RuntimeException for missing view file')]
    public function throws_for_missing_view(): void
    {
        $view = new View(Container::getInstance(), $this->tmpDir, $this->tmpDir . '/components');

        try {
            $view->render('does_not_exist');
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertTrue(str_contains($e->getMessage(), 'View file not found'));
        }
    }
}
