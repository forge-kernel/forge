<?php

declare(strict_types=1);

namespace App\Modules\ForgeStaticHtml\Commands;

use App\Modules\ForgeStaticHtml\StaticGenerator;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Config\Config;

#[Cli(
    command: 'site:generate',
    description: 'Generate static HTML version of the site',
    usage: 'site:generate [--output-dir=PATH]',
    examples: [
        'site:generate',
        'site:generate --output-dir=public/static',
        'site:generate --output-dir=custom-html'
    ]
)]
final class GenerateStaticCommand extends Command
{
    use OutputHelper;
    use Wizard;

    #[Arg(
        name: 'output-dir',
        description: 'Directory to generate the static HTML files (default: public/static)',
        default: 'public/static',
        required: false
    )]
    private string $outputDir;

    public function __construct(private readonly Config $config)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        try {
            $config = $this->config->get('forge_static_html', []);
            $generator = new StaticGenerator($config);

            $this->info("Starting static site generation to '{$this->outputDir}'...");
            $generator->generate($this->outputDir);
            $this->success("Static site generated successfully in '{$this->outputDir}'!");

            return 0;
        } catch (\Throwable $e) {
            $this->error("Static site generation failed!");
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}