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
use Forge\Core\Module\ForgeIcon;
use Forge\Traits\InjectsAssets;
use \App\Modules\ForgeDebugBar\DebugBar;
use Forge\Core\Config\Config;

#[Module(
    name: 'ForgeDebugBar',
    version: '1.3.5',
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

    private static ?MemoryCollector $memoryCollector = null;

    public function register(Container $container): void
    {
        $container->bind(DebugBar::class, DebugBar::class);
        $this->setupConfigDefaults($container);
    }

    private function setupConfigDefaults(Container $container): void
    {
        /** @var Config $config */
        $config = $container->get(Config::class);
        $config->set('forge_debug_bar.enabled', env('FORGE_DEBUG_BAR_ENABLED', true));
    }

    #[RouterHookAttribute(RouterHookName::AFTER_REQUEST)]
    public function onAfterRequest(Request $request, Response $response): void
    {
        //add_timeline_event('onAfterRequest', 'end');

        self::$memoryCollector = MemoryCollector::instance();

        $debugbar = $this->getDebugbarInstance();

        $requestData = RequestCollector::collect($request);
        $debugbar->addCollector('request', function () use ($requestData) {
            return $requestData;
        });

        $sessionData = SessionCollector::collect();
        $debugbar->addCollector('session', function () use ($sessionData) {
            return $sessionData;
        });

        $debugbar->addCollector('memory', function () {
            return self::$memoryCollector ? self::$memoryCollector->getMemoryUsage() : ['error' => 'Memory collector not initialized'];
        });

        $debugbar->addCollector('time', function ($startTime) {
            return TimeCollector::collect($startTime);
        });

        try {
            $container = Container::getInstance();

            if ($container->has(TimelineCollector::class)) {
                /** @var TimelineCollector $timelineCollector */
                $timelineCollector = $container->get(TimelineCollector::class);
                $timelineData = $timelineCollector->collect($request);
                $debugbar->addCollector('timeline', function () use ($timelineData) {
                    return $timelineData;
                });
            }

            if ($container->has(ViewCollector::class)) {
                /** @var ViewCollector $viewCollector */
                $viewCollector = $container->get(ViewCollector::class);
                $viewData = $viewCollector->collect($request);
                $debugbar->addCollector('views', function () use ($viewData) {
                    return $viewData;
                });
            }

            if ($container->has(ExceptionCollector::class)) {
                /** @var ExceptionCollector $exceptionCollector */
                $exceptionCollector = $container->get(ExceptionCollector::class);
                $exceptionData = $exceptionCollector->collect($request);
                $debugbar->addCollector('exceptions', function () use ($exceptionData) {
                    return $exceptionData;
                });
            }

            if ($container->has(DatabaseCollector::class)) {
                /** @var DatabaseCollector $databaseCollector */
                $databaseCollector = $container->get(DatabaseCollector::class);
                $databaseData = $databaseCollector->collect($request);
                $debugbar->addCollector('Database', function () use ($databaseData) {
                    return $databaseData;
                });
            }
        } catch (\Throwable $e) {
        }

        $debugbar->addCollector('messages', function ($startTime) {
            return MessageCollector::collect($startTime);
        });

        $routeData = RouteCollector::collect();
        $debugbar->addCollector('route', function () use ($routeData) {
            return $routeData;
        });

        $this->registerDebugBarAssets();
        $this->injectAssets($response);
        $this->storeLatestDataForHub();
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
