<?php

declare(strict_types=1);

namespace Modules\ForgeStaticHtml\Commands;

use Modules\ForgeStaticHtml\StaticGenerator;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Config\Config;

#[Cli(
    command: 'site:generate',
    description: 'Generate a static HTML version of the site by crawling routes and following links',
    usage: 'site:generate [--output-dir=PATH] [--max-depth=N] [--include-path=PATH]',
    examples: [
        'site:generate',
        'site:generate --output-dir=public/static --max-depth=5',
        'site:generate --include-path=/docs,/blog --no-clean',
        'site:generate --no-assets --crawl-only',
    ]
)]
final class GenerateStaticCommand extends Command
{
    use OutputHelper;
    use Wizard;

    #[Arg(
        name: 'output-dir',
        description: 'Override the output directory',
        default: null,
        required: false,
    )]
    private ?string $outputDir = null;

    #[Arg(
        name: 'max-depth',
        description: 'Maximum link crawl depth (0 = no crawl)',
        default: null,
        required: false,
    )]
    private ?int $maxDepth = null;

    #[Arg(
        name: 'include-path',
        description: 'Comma-separated list of URL prefixes to include',
        default: null,
        required: false,
    )]
    private ?string $includePath = null;

    #[Arg(
        name: 'no-clean',
        description: 'Skip cleaning the output directory before generation',
        default: false,
        required: false,
    )]
    private bool $noClean = false;

    #[Arg(
        name: 'no-assets',
        description: 'Skip copying asset files',
        default: false,
        required: false,
    )]
    private bool $noAssets = false;

    #[Arg(
        name: 'crawl-only',
        description: 'Only crawl links, skip database-driven dynamic routes',
        default: false,
        required: false,
    )]
    private bool $crawlOnly = false;

    public function __construct(private readonly Config $config)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        $config = $this->config->get('forge_static_html', []);

        if ($this->outputDir !== null) {
            $config['output_dir'] = $this->outputDir;
        }

        if ($this->maxDepth !== null) {
            $config['max_depth'] = $this->maxDepth;
        }

        if ($this->includePath !== null) {
            $config['include_paths'] = array_map('trim', explode(',', $this->includePath));
        }

        if ($this->noClean) {
            $config['clean_build'] = false;
        }

        if ($this->noAssets) {
            $config['copy_assets'] = false;
            $config['asset_discovery'] = false;
        }

        if ($this->crawlOnly) {
            $config['dynamic_routes'] = [];
        }

        try {
            $generator = new StaticGenerator($config);
            $this->info('Starting static site generation...');
            $generator->generate();
            $this->success('Static site generated successfully!');
            return 0;
        } catch (\Throwable $e) {
            $this->error('Static site generation failed!');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
