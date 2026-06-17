<?php

declare(strict_types=1);

namespace App\Modules\ForgeStaticGen\Commands;

use App\Modules\ForgeStaticGen\Contracts\ForgeStaticGenInterface;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;
use Forge\Core\DI\Container;

#[Cli(
    command: 'site:build',
    description: 'Build the static site from Markdown content',
    usage: 'site:build [--content-dir=PATH]',
    examples: [
        'site:build',
        'site:build --content-dir=docs',
        'site:build --content-dir=custom-content'
    ]
)]
final class StaticGenBuildCommand extends Command
{
    use OutputHelper;
    use Wizard;

    #[Arg(
        name: 'content-dir',
        description: 'Directory containing the Markdown content (default: docs)',
        default: 'docs',
        required: false
    )]
    private string $contentDir;

    public function execute(array $args): int
    {
        $this->wizard($args);

        $this->info("Starting static site generation from '{$this->contentDir}'...");

        $container = Container::getInstance();
        /** @var ForgeStaticGenInterface $staticGen */
        $staticGen = $container->get(ForgeStaticGenInterface::class);

        $fullPath = BASE_PATH . '/' . $this->contentDir;

        try {
            $staticGen->build($fullPath);
            $this->success("Static site generation completed successfully!");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Static site generation failed!");
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}