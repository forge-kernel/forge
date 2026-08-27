<?php

declare(strict_types=1);

namespace Modules\ForgeSockets;

use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Traits\RegistersCommands;
use Modules\ForgeSockets\Commands\SocketServeCommand;
use Modules\ForgeSockets\Handlers\EchoHandler;

/**
 * ForgeSockets — production-grade WebSocket primitives for the NovaSpace
 * platform, built on PHP's built-in networking (no external dependencies).
 *
 * Implements RFC 6455 (opening handshake, framing, control frames, close
 * handshake) on top of a non-blocking stream_select event loop, in the same
 * spirit as ForgeEvents: a Forge capability that ships a long-running CLI
 * process (`modules:socket:serve`) and stays transport-agnostic — any app or
 * module can plug a {@see MessageHandlerInterface} into it.
 */
#[Module(
    name: 'ForgeSockets',
    version: '0.1.0',
    description: 'Zero-dependency RFC 6455 WebSocket primitives: handshake, framing, non-blocking event loop and a socket:serve worker.',
    author: 'NovaSpace',
    license: 'MIT',
    type: 'networking',
    tags: ['websocket', 'socket', 'realtime', 'event-loop', 'rfc6455'],
)]
#[ConfigDefaults(defaults: [
    'forge_sockets' => [
        'host' => '127.0.0.1',
        'port' => 8282,
        'max_payload' => 65536,
        'heartbeat_seconds' => 30,
        'workers' => 1,
        'handler' => EchoHandler::class,
        'authenticator' => null,
    ],
])]
final class ForgeSocketsModule
{
    use RegistersCommands;

    public function register(Container $container): void
    {
        $this->setupConfigDefaults($container);
    }

    protected function commands(): array
    {
        return [
            SocketServeCommand::class,
        ];
    }

    private function setupConfigDefaults(Container $container): void
    {
        $config = $container->get(Config::class);
        $config->set('forge_sockets.host', env('SOCKET_HOST', '127.0.0.1'));
        $config->set('forge_sockets.port', (int) env('SOCKET_PORT', 8282));
        $config->set('forge_sockets.max_payload', (int) env('SOCKET_MAX_PAYLOAD', 65536));
        $config->set('forge_sockets.heartbeat_seconds', (int) env('SOCKET_HEARTBEAT', 30));
        $config->set('forge_sockets.workers', (int) env('SOCKET_WORKERS', 1));
    }
}