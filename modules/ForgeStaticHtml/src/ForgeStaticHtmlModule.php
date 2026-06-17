<?php

namespace App\Modules\ForgeStaticHtml;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;

#[Module(
  name: 'ForgeMarkDown',
  description: 'Static site generator for Forge Framework',
  isCli: true,
  author: 'Forge Team',
  license: 'MIT',
  type: 'html',
  tags: ['html', 'static', 'site', 'generator']
)]
#[Service()]
#[Provides(interface: StaticGenerator::class, version: '0.2.0')]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[ConfigDefaults(defaults: [
  'forge_static_html' => [
    'output_dir' => 'public/static',
    'base_url' => '/',
    'clean_build' => true,
    'copy_assets' => true,
    'asset_dirs' => [
      'public/assets',
      'public/images'
    ],
    'include_paths' => [
      '/docs2',
    ],
    'dynamic_routes' => [
      'documentation' => [
        'route_pattern' => '/docs2/{category}/{slug}',
        'data_source' => 'Database',
        'options' => [
          'categories_table' => 'categories',
          'sections_table' => 'sections',
          'category_slug_column' => 'slug',
          'section_slug_column' => 'slug',
          'section_category_id_column' => 'category_id',
          'batch_size' => 100,
        ],
      ],
    ],
  ]
])]
class ForgeStaticHtmlModule
{
  public function register(Container $container): void
  {
  }
}
