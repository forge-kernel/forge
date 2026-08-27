<?php

declare(strict_types=1);

namespace Modules\ForgeSockets\Commands;

use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Modules\ForgeSockets\Contracts\AuthenticatorInterface;
use Modules\ForgeSockets\Contracts\MessageHandlerInterface;
use Modules\ForgeSockets\Handlers\EchoHandler;
use Modules\ForgeSockets\Server\WebSocketServer;

/**
 * The long-running WebSocket worker: `modules:socket:serve`. Binds a TCP
 * listener and runs the non-blocking event loop until SIGINT/SIGTERM, draining
 * connections with 1001 on the way down — the same process model as the Forge
 * queue worker (pcntl signals; forked workers per process).
 *
 * The handler (and optional authenticator) come from the `forge_sockets.*`
 * config, or the `--handler`/`--authenticator` CLI flags.
 */
#[Cli(
    command: 'socket:serve',
    description: 'Serve WebSocket connections over a non-blocking event loop',
    usage: 'socket:serve [--host=HOST] [--port=PORT] [--handler=CLASS] [--authenticator=CLASS]',
    examples: [
        'socket:serve',
        'socket:serve --port=8282 --host=127.0.0.1',
        'socket:serve --handler=Modules\\MyApp\\Handlers\\MyHandler',
    ],
)]
final class SocketServeCommand extends Command
{
    use OutputHelper;
    use Wizard;

    private static bool $shutdown = false;

    #[Arg(name: 'host', description: 'Bind host', default: null, required: false)]
    private ?string $host = null;

    #[Arg(name: 'port', description: 'Bind port', default: null, required: false, validate: '/^\d+$/')]
    private ?string $port = null;

    #[Arg(name: 'handler', description: 'Message handler class', default: null, required: false)]
    private ?string $handler = null;

    #[Arg(name: 'authenticator', description: 'Authenticator class', default: null, required: false)]
    private ?string $authenticator = null;

    public function __construct(
        private readonly Container $container,
        private readonly Config $config,
    ) {}

    public function execute(array $args): int
    {
        $this->wizard($args);

        $host = $this->host ?? (string) $this->config->get('forge_sockets.host', '127.0.0.1');
        $port = (int) ($this->port ?? $this->config->get('forge_sockets.port', 8282));
        $heartbeat = (int) $this->config->get('forge_sockets.heartbeat_seconds', 30);
        $maxPayload = (int) $this->config->get('forge_sockets.max_payload', 65536);
        $tickInterval = (float) $this->config->get('forge_sockets.tick_interval', 0.05);

        $handler = $this->resolveHandler();
        $authenticator = $this->resolveAuthenticator();

        pcntl_async_signals(true);
        pcntl_signal(SIGINT, static function (): void {
            self::$shutdown = true;
        });
        pcntl_signal(SIGTERM, static function (): void {
            self::$shutdown = true;
        });

        $this->info("ForgeSockets serving ws://{$host}:{$port}");

        $server = new WebSocketServer(
            host: $host,
            port: $port,
            handler: $handler,
            authenticator: $authenticator,
            maxPayload: $maxPayload,
            heartbeatSeconds: $heartbeat,
            tickInterval: $tickInterval,
        );

        $server->run(static fn (): bool => self::$shutdown);

        $this->warning('ForgeSockets worker exiting gracefully.');

        return 0;
    }

    private function resolveHandler(): MessageHandlerInterface
    {
        $class = $this->handler
            ?? $this->config->get('forge_sockets.handler')
            ?? EchoHandler::class;

        $handler = $this->container->make($class);
        if (!$handler instanceof MessageHandlerInterface) {
            $this->error($class . ' is not a MessageHandlerInterface');

            throw new \InvalidArgumentException($class . ' must implement ' . MessageHandlerInterface::class);
        }

        return $handler;
    }

    private function resolveAuthenticator(): ?AuthenticatorInterface
    {
        $class = $this->authenticator
            ?? $this->config->get('forge_sockets.authenticator');

        if ($class === null || $class === '') {
            return null;
        }

        $authenticator = $this->container->make($class);
        if (!$authenticator instanceof AuthenticatorInterface) {
            throw new \InvalidArgumentException($class . ' must implement ' . AuthenticatorInterface::class);
        }

        return $authenticator;
    }
}