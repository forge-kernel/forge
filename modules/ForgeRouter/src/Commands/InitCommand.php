<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Commands;

use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;

#[Cli(
    command: 'modules:forge-router:init',
    description: 'Scaffold public/ entry point, .htaccess, and ForgeRouter config files',
    usage: 'modules:forge-router:init [--force]',
    examples: [
        'modules:forge-router:init',
        'modules:forge-router:init --force'
    ]
)]
final class InitCommand extends Command
{
    use Wizard;
    private const string STUBS_DIR = __DIR__ . '/../resources/stubs';

    private const array FILES = [
        ['stub' => 'public-index.stub', 'target' => '/public/index.php'],
        ['stub' => 'htaccess.stub', 'target' => '/public/.htaccess'],
        ['stub' => 'root-index.stub', 'target' => '/index.php'],
        ['stub' => 'middleware-config.stub', 'target' => '/config/middleware.php'],
        ['stub' => 'forge-router-config.stub', 'target' => '/config/forge_router.php'],
    ];

    #[Arg(
        name: 'force',
        description: 'Overwrite existing files',
        default: false,
        required: false,
    )]
    private bool $force;

    public function execute(array $args): int
    {
        $this->wizard($args);

        $anyCreated = false;

        foreach (self::FILES as $file) {
            $targetPath = BASE_PATH . $file['target'];

            if (is_file($targetPath) && !$this->force) {
                if ($file['target'] === '/config/middleware.php') {
                    $this->mergeMiddlewareConfig($targetPath);
                    $this->success("Merged: {$file['target']}");
                    $anyCreated = true;
                    continue;
                }
                $this->comment("Skipped (exists): {$file['target']}");
                continue;
            }

            $dir = dirname($targetPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $stubPath = self::STUBS_DIR . '/' . $file['stub'];
            $content = file_get_contents($stubPath);

            if ($file['target'] === '/config/middleware.php' && is_file($targetPath)) {
                $content = $this->mergeMiddlewareConfigContent($targetPath, $stubPath);
            }

            file_put_contents($targetPath, $content);

            $this->success("Created: {$file['target']}");
            $anyCreated = true;
        }

        if (!$anyCreated) {
            $this->info('All files already exist. Use --force to overwrite.');
        }

        return 0;
    }

    private function mergeMiddlewareConfigContent(string $existingPath, string $stubPath): string
    {
        $existing = @include $existingPath;
        $defaults = @include $stubPath;

        if (!is_array($existing)) {
            return file_get_contents($stubPath);
        }
        if (!is_array($defaults)) {
            return file_get_contents($stubPath);
        }

        $merged = $defaults;
        foreach ($existing as $group => $middlewares) {
            if (!isset($merged[$group])) {
                $merged[$group] = $middlewares;
            }
        }

        return $this->formatMiddlewareConfig($merged);
    }

    private function formatMiddlewareConfig(array $config): string
    {
        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'return [';

        foreach ($config as $group => $middlewares) {
            $lines[] = "    \"{$group}\" => [";
            foreach ($middlewares as $mw) {
                $mw = ltrim($mw, '\\');
                $lines[] = "        \\{$mw}::class,";
            }
            $lines[] = '    ],';
        }

        $lines[] = '];';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function mergeMiddlewareConfig(string $targetPath): void
    {
        $stubPath = self::STUBS_DIR . '/middleware-config.stub';
        $content = $this->mergeMiddlewareConfigContent($targetPath, $stubPath);
        file_put_contents($targetPath, $content);
    }
}
