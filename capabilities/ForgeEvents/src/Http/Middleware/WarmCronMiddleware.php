<?php

declare(strict_types=1);

namespace Capability\ForgeEvents\Http\Middleware;

use Capability\ForgeEvents\Injectable\Managers\MaintenanceManager;
use Capability\ForgeRouter\Http\Middleware;
use Capability\ForgeRouter\Http\Request;
use Capability\ForgeRouter\Http\Response;
use Forge\Core\Config\Config;

/**
 * Warm the app's own maintenance chains: on every web request it seeds any
 * scheduled cron event (listed in the `forge_events.scheduled_events` config)
 * that does not already have a pending job, so maintenance runs without waiting
 * for a user action. Idempotent and cheap (one batched query; dispatches only
 * the missing chains).
 */
final class WarmCronMiddleware extends Middleware
{
    private const array SKIP_PATHS = ['/health', '/healthz', '/ping', '/status'];

    public function __construct(
        private readonly Config $config,
        private readonly MaintenanceManager $maintenance,
    ) {}

    public function handle(Request $request, callable $next): Response
    {
        if (!in_array($request->getPath(), self::SKIP_PATHS, true)) {
            $this->maintenance->seedAll($this->config->get('forge_events.scheduled_events', []));
        }

        return $next($request);
    }
}