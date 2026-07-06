<?php

declare(strict_types=1);

namespace Modules\ForgeLogger\Services;

use Forge\Core\Config\Config;
use Forge\Core\Contracts\LoggerInterface;
use Forge\Core\Module\Attributes\Provides;
use Modules\ForgeLogger\Contracts\ForgeLoggerInterface;
use Modules\ForgeLogger\Contracts\LogDriverInterface;
use Modules\ForgeLogger\Contracts\LogLevel;
use Modules\ForgeLogger\Drivers\FileDriver;
use Modules\ForgeLogger\Drivers\NullDriver;
use Modules\ForgeLogger\Drivers\SysLogDriver;

#[Provides(interface: LoggerInterface::class, version: '0.2.0')]
final class ForgeLoggerService implements ForgeLoggerInterface
{
    private array $drivers = [];
    private string $path;
    private string $driver;
    private LogLevel $minLevel;
    private int $maxFileSize;

    public function __construct(private Config $config)
    {
        $this->driver = $this->config->get('forge_logger.driver');
        $this->path = $this->config->get('forge_logger.path');
        $this->minLevel = LogLevel::fromString(
            $this->config->get('forge_logger.min_level', 'DEBUG')
        );
        $this->maxFileSize = (int) $this->config->get('forge_logger.max_file_size', '0');
        $this->initService();
    }

    private function initService(): void
    {
        $this->registerDriver('file', new FileDriver($this->path, $this->maxFileSize));
        $this->registerDriver('syslog', new SysLogDriver());
        $this->registerDriver('null', new NullDriver());
    }

    public function registerDriver(string $name, LogDriverInterface $driver): void
    {
        $this->drivers[$name] = $driver;
    }

    public function log(string $message, string $level = 'INFO', array $context = []): void
    {
        $logLevel = LogLevel::fromString($level);

        if (!$this->shouldLog($logLevel)) {
            return;
        }

        $driver = $this->drivers[$this->driver] ?? $this->drivers['null'];
        $driver->write($this->formatMessage($logLevel, $message, $context));
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log($message, 'DEBUG', $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log($message, 'INFO', $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log($message, 'WARNING', $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log($message, 'ERROR', $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log($message, 'CRITICAL', $context);
    }

    public function exception(\Throwable $e, array $context = []): void
    {
        $context['exception'] = [
            'class' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];
        $this->log($e->getMessage(), 'ERROR', $context);
    }

    private function shouldLog(LogLevel $level): bool
    {
        return $level->priority() >= $this->minLevel->priority();
    }

    private function formatMessage(LogLevel $level, string $message, array $context = []): string
    {
        $safeMessage = str_replace(["\r\n", "\n", "\r"], ' ', $message);
        $line = sprintf(
            '[%s] [%s] %s',
            date('Y-m-d H:i:s'),
            $level->value,
            $safeMessage
        );

        if (!empty($context)) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }

        return $line;
    }
}
