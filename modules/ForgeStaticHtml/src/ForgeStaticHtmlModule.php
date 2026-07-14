<?php

declare(strict_types=1);

namespace Modules\ForgeStaticHtml;

use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Requires;
use Forge\Core\Module\Attributes\Structure;
use Forge\Core\Module\Traits\RegistersCommands;

#[Module(
    name: 'ForgeStaticHtml',
    description: 'Static HTML generator with link crawling, depth control, and asset management',
    isCli: true,
    author: 'Forge Team',
    license: 'MIT',
    type: 'html',
    tags: ['html', 'static', 'site', 'generator', 'crawler']
)]
#[Structure(structure: [
    'services' => 'src/Services',
    'commands' => 'src/Commands',
    'tests' => 'src/tests',
])]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Requires(module: 'forge-router')]
#[ConfigDefaults(defaults: [
    'forge_static_html' => [
        'output_dir' => 'public/static',
        'base_url' => 'http://localhost',
        'clean_build' => true,
        'max_depth' => 3,
        'include_paths' => ['/'],
        'exclude_paths' => ['/admin', '/api', '/_debug'],
        'copy_assets' => true,
        'asset_discovery' => true,
        'asset_dirs' => ['public/assets', 'public/images'],
        'dynamic_routes' => [],
    ]
])]
final class ForgeStaticHtmlModule
{
    use RegistersCommands;

    public function register(Container $container): void
    {
        $container->bind(StaticGenerator::class, function () use ($container): StaticGenerator {
            $config = $container->get(Config::class);
            return new StaticGenerator($config->get('forge_static_html', []));
        });
    }

    protected function commands(): array
    {
        return [
            \Modules\ForgeStaticHtml\Commands\GenerateStaticCommand::class,
        ];
    }
}
