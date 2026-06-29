<?php

declare(strict_types=1);

namespace Modules\ForgeLanguage;

use Forge\Core\DI\Container;
use Modules\ForgeRouter\Http\Request;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Modules\ForgeRouter\Events\RouterHookAttribute;
use Modules\ForgeRouter\Events\RouterHookName;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\Structure;

#[Structure(structure: [
    'controllers' => 'src/Controllers',
    'services' => 'src/Services',
    'migrations' => 'src/Database/Migrations',
    'views' => 'src/UI/views',
    'components' => 'src/UI/views/components',
    'commands' => 'src/Commands',
    'events' => 'src/Events',
    'tests' => 'src/tests',
    'models' => 'src/Models',
    'dto' => 'src/Dto',
    'seeders' => 'src/Database/Seeders',
    'middlewares' => 'src/Middlewares',
    'languages' => 'src/Languages'
])]


#[Service]
#[Module(name: 'ForgeLanguage', version: '0.2.3', description: 'Multi language support to extend Forge Kernel', order: 40, author: 'Your Name', license: 'MIT', tags: [])]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-kernel/kernel-module-registry')]
#[ConfigDefaults(defaults: [
    "forge_language" => [
        'languages' => [
            'en' => [
                'label' => 'English',
                'flag' => '🇺🇸',
            ],

            'es' => [
                'label' => 'Español',
                'flag' => '🇪🇸',
            ],
        ],
        'default' => 'en',
    ]
])]
final class ForgeLanguageModule
{
    use OutputHelper;
    public function register(Container $container): void
    {

    }

    #[RouterHookAttribute(RouterHookName::BEFORE_REQUEST)]
    public function onBeforeRequest(Request $request): void
    {
        $container = Container::getInstance();
        $container->setInstance(Request::class, $request);
    }

}
