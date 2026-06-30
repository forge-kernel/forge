<?php

declare(strict_types=1);

namespace Modules\ForgeLanguage;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Requires;
use Modules\ForgeRouter\Http\Request;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Modules\ForgeRouter\Events\RouterHookAttribute;
use Modules\ForgeRouter\Events\RouterHookName;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;

#[Module(name: 'ForgeLanguage', version: '0.2.4', description: 'Multi language support to extend Forge Kernel', order: 40, author: 'Your Name', license: 'MIT', tags: [])]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Requires(module: "forge-router")]
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
    #[RouterHookAttribute(RouterHookName::BEFORE_REQUEST)]
    public function onBeforeRequest(Request $request): void
    {
        $container = Container::getInstance();
        $container->setInstance(Request::class, $request);
    }

}
