<?php

namespace App\Modules\ForgeDebugBar;

use App\Modules\ForgeDebugBar\Collectors\MemoryCollector;
use App\Modules\ForgeDebugBar\Collectors\MessageCollector;
use App\Modules\ForgeDebugBar\Collectors\RequestCollector;
use App\Modules\ForgeDebugBar\Collectors\RouteCollector;
use App\Modules\ForgeDebugBar\Collectors\SessionCollector;
use App\Modules\ForgeDebugBar\Collectors\TimeCollector;
use App\Modules\ForgeRouter\Collectors\DatabaseCollector;
use App\Modules\ForgeRouter\Collectors\ExceptionCollector;
use App\Modules\ForgeRouter\Collectors\TimelineCollector;
use App\Modules\ForgeRouter\Collectors\ViewCollector;
use Forge\Core\DI\Container;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\HubItem;
use App\Modules\ForgeRouter\Events\RouterHookAttribute;
use App\Modules\ForgeRouter\Events\RouterHookName;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Structure;
use Forge\Core\Module\ForgeIcon;
use Forge\Traits\InjectsAssets;
use \App\Modules\ForgeDebugBar\DebugBar;
use Forge\Core\Config\Config;

#[Structure(structure: [
    'controllers' => 'src/Controllers',
    'services' => 'src/Services',
    'views' => 'src/views',
    'components' => 'src/UI/views/components',
    'assets' => 'src/UI/assets',
])]
#[Module(
    name: 'ForgeDebugBar',
    version: '1.3.6',
    description: 'A debug bar by Forge',
    order: 3,
    author: 'Forge Team',
    license: 'MIT',
    type: 'generic',
    tags: ['generic', 'debug', 'debug-bar', 'debug-bar-system', 'debug-bar-library', 'debug-bar-framework']
)]
#[HubItem(label: 'Debug Bar', route: '/hub/debugbar', icon: ForgeIcon::COG, order: 6)]
#[Compatibility(framework: '>=4.15.11', php: '>=8.3')]
#[ConfigDefaults(defaults: [
    'forge_debug_bar' => [
        'enabled' => true
    ]
])]
#[PostInstall(command: 'asset:link', args: ['--type=module', '--module=forge-debug-bar'])]
#[PostUninstall(command: 'asset:unlink', args: ['--type=module', '--module=forge-debug-bar'])]
class DebugBarModule
{
    use InjectsAssets;

    #[RouterHookAttribute(RouterHookName::AFTER_REQUEST)]
    public function onAfterRequest(Request $request, Response $response): void
    {
        $debugbar = $this->getDebugbarInstance();

        $this->registerCoreCollectors($debugbar, $request);
        $this->registerCrossModuleCollectors($debugbar, $request);
        $this->registerTabs($debugbar);

        $this->registerDebugBarAssets();
        $this->injectAssets($response);
        $this->storeLatestDataForHub();
    }

    private function registerCoreCollectors(DebugBar $debugbar, Request $request): void
    {
        $requestData = RequestCollector::collect($request);
        $debugbar->addCollector('request', function () use ($requestData) {
            return $requestData;
        });

        $sessionData = SessionCollector::collect();
        $debugbar->addCollector('session', function () use ($sessionData) {
            return $sessionData;
        });

        $debugbar->addCollector('memory', function () {
            return MemoryCollector::instance()->getMemoryUsage();
        });

        $debugbar->addCollector('time', function ($startTime) {
            return TimeCollector::collect($startTime);
        });

        $debugbar->addCollector('messages', function ($startTime) {
            return MessageCollector::collect($startTime);
        });

        $routeData = RouteCollector::collect();
        $debugbar->addCollector('route', function () use ($routeData) {
            return $routeData;
        });
    }

    private function registerCrossModuleCollectors(DebugBar $debugbar, Request $request): void
    {
        try {
            $container = Container::getInstance();

            if ($container->has(TimelineCollector::class)) {
                $timelineCollector = $container->get(TimelineCollector::class);
                $debugbar->addCollector('timeline', function () use ($timelineCollector, $request) {
                    return $timelineCollector->collect($request);
                });
            }

            if ($container->has(ViewCollector::class)) {
                $viewCollector = $container->get(ViewCollector::class);
                $debugbar->addCollector('views', function () use ($viewCollector, $request) {
                    return $viewCollector->collect($request);
                });
            }

            if ($container->has(ExceptionCollector::class)) {
                $exceptionCollector = $container->get(ExceptionCollector::class);
                $debugbar->addCollector('exceptions', function () use ($exceptionCollector, $request) {
                    return $exceptionCollector->collect($request);
                });
            }

            if ($container->has(DatabaseCollector::class)) {
                $databaseCollector = $container->get(DatabaseCollector::class);
                $debugbar->addCollector('Database', function () use ($databaseCollector, $request) {
                    return $databaseCollector->collect($request);
                });
            }
        } catch (\Throwable) {
        }
    }

    private function registerTabs(DebugBar $debugbar): void
    {
        $debugbar->registerTab('overview', 'Overview', 'ForgeDebugBar:panels/overview', options: ['data_key' => 'request']);
        $debugbar->registerTab('console', 'Console', 'ForgeDebugBar:panels/console', options: ['data_key' => 'messages']);
        $debugbar->registerTab('errors', 'Errors', 'ForgeDebugBar:panels/errors', options: ['data_key' => 'exceptions']);
        $debugbar->registerTab('database', 'Database', 'ForgeDebugBar:panels/database', options: ['data_key' => 'Database']);
        $debugbar->registerTab('router', 'Router', 'ForgeDebugBar:panels/router', options: ['data_key' => 'route']);
        $debugbar->registerTab('templates', 'Templates', 'ForgeDebugBar:panels/templates', options: ['data_key' => 'views']);
        $debugbar->registerTab('state', 'State', 'ForgeDebugBar:panels/state', options: ['data_key' => 'session']);
        $debugbar->registerTab('resources', 'Resources', 'ForgeDebugBar:panels/resources', options: ['data_key' => 'resources']);
        $debugbar->registerTab('timeline', 'Timeline', 'ForgeDebugBar:panels/timeline', options: ['data_key' => 'timeline']);
    }

    private function storeLatestDataForHub(): void
    {
        try {
            $container = Container::getInstance();
            if ($container->has(\App\Modules\ForgeDebugBar\Services\DebugBarHubService::class)) {
                $hubService = $container->get(\App\Modules\ForgeDebugBar\Services\DebugBarHubService::class);
                $hubService->storeLatestData();
            }
        } catch (\Throwable) {
        }
    }

    private function registerDebugBarAssets(): void
    {
        $debugbar = $this->getDebugbarInstance();
        $container = Container::getInstance();

        if (!$debugbar->shouldEnableDebugBar($container)) {
            return;
        }

        $cssLinkTag = '<link rel="stylesheet" href="/assets/modules/forge-debug-bar/css/debugbar.css">';
        $this->registerAsset(assetHtml: $cssLinkTag, beforeTag: '</head>');

        $debugBarHtml = $debugbar->render();

        $jsScriptTag = '<script src="/assets/modules/forge-debug-bar/js/debugbar.js"></script>';
        $this->registerAsset(assetHtml: $debugBarHtml . "\n" . $jsScriptTag, beforeTag: '</body>');
    }

    private function getDebugbarInstance(): DebugBar
    {
        return DebugBar::getInstance();
    }
}
