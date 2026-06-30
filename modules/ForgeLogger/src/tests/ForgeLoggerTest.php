<?php

declare(strict_types=1);

namespace Modules\ForgeLogger\Tests;

use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Modules\ForgeLogger\Contracts\LogDriverInterface;
use Modules\ForgeLogger\Contracts\LogLevel;
use Modules\ForgeLogger\Drivers\FileDriver;
use Modules\ForgeLogger\Services\ForgeLoggerService;
use Forge\Core\Config\Config;

#[Group("logging")]
final class ForgeLoggerTest extends TestCase
{
    private ForgeLoggerService $logger;
    private ArrayDriver $driver;
    private Config $config;

    #[BeforeEach]
    public function setUp(): void
    {
        $configDir = sys_get_temp_dir() . '/forge-config-' . uniqid();
        @mkdir($configDir, 0755, true);
        file_put_contents($configDir . '/app.php', '<?php return [];');
        file_put_contents($configDir . '/forge_logger.php', '<?php return [];');

        $this->config = new Config($configDir);
        $this->config->set('forge_logger.driver', 'file');
        $this->config->set('forge_logger.path', '/tmp/forge-test.log');
        $this->config->set('forge_logger.min_level', 'DEBUG');
        $this->config->set('forge_logger.max_file_size', '0');

        $this->logger = new ForgeLoggerService($this->config);
        $this->driver = new ArrayDriver();

        $ref = new \ReflectionProperty($this->logger, 'drivers');
        $ref->setAccessible(true);
        $ref->setValue($this->logger, ['file' => $this->driver]);
    }

    #[Test("debug writes message through configured driver")]
    public function debug_logs_via_driver(): void
    {
        $this->logger->debug('test message');

        $this->assertCount(1, $this->driver->messages);
        $this->assertStringContainsString('[DEBUG] test message', $this->driver->messages[0]);
    }

    #[Test("log with context appends json")]
    public function log_with_context(): void
    {
        $this->logger->log('user action', 'INFO', ['user_id' => 42, 'action' => 'login']);

        $this->assertCount(1, $this->driver->messages);
        $this->assertStringContainsString('[INFO] user action', $this->driver->messages[0]);
        $this->assertStringContainsString('"user_id":42', $this->driver->messages[0]);
        $this->assertStringContainsString('"action":"login"', $this->driver->messages[0]);
    }

    #[Test("LogLevel enum values and priority")]
    public function log_level_enum_values(): void
    {
        $this->assertSame('DEBUG', LogLevel::DEBUG->value);
        $this->assertSame('INFO', LogLevel::INFO->value);
        $this->assertSame('WARNING', LogLevel::WARNING->value);
        $this->assertSame('ERROR', LogLevel::ERROR->value);
        $this->assertSame('CRITICAL', LogLevel::CRITICAL->value);

        $this->assertSame(0, LogLevel::DEBUG->priority());
        $this->assertSame(1, LogLevel::INFO->priority());
        $this->assertSame(2, LogLevel::WARNING->priority());
        $this->assertSame(3, LogLevel::ERROR->priority());
        $this->assertSame(4, LogLevel::CRITICAL->priority());
    }

    #[Test("messages below min level are filtered out")]
    public function level_filtering_below_min(): void
    {
        $this->config->set('forge_logger.min_level', 'ERROR');
        $logger = new ForgeLoggerService($this->config);
        $driver = new ArrayDriver();
        $ref = new \ReflectionProperty($logger, 'drivers');
        $ref->setAccessible(true);
        $ref->setValue($logger, ['file' => $driver]);

        $logger->debug('should be filtered');
        $logger->info('should be filtered');
        $logger->warning('should be filtered');
        $logger->error('should pass');
        $logger->critical('should also pass');

        $this->assertCount(2, $driver->messages);
        $this->assertStringContainsString('[ERROR] should pass', $driver->messages[0]);
        $this->assertStringContainsString('[CRITICAL] should also pass', $driver->messages[1]);
    }

    #[Test("convenience methods produce correct level in output")]
    public function convenience_methods(): void
    {
        $this->logger->info('info msg');
        $this->logger->warning('warn msg');
        $this->logger->error('error msg');
        $this->logger->critical('critical msg');

        $this->assertCount(4, $this->driver->messages);
        $this->assertStringContainsString('[INFO] info msg', $this->driver->messages[0]);
        $this->assertStringContainsString('[WARNING] warn msg', $this->driver->messages[1]);
        $this->assertStringContainsString('[ERROR] error msg', $this->driver->messages[2]);
        $this->assertStringContainsString('[CRITICAL] critical msg', $this->driver->messages[3]);
    }

    #[Test("exception method includes class, file, line and trace")]
    public function exception_logging(): void
    {
        try {
            throw new \RuntimeException('something broke');
        } catch (\RuntimeException $e) {
            $this->logger->exception($e);
        }

        $this->assertCount(1, $this->driver->messages);
        $this->assertStringContainsString('[ERROR] something broke', $this->driver->messages[0]);
        $this->assertStringContainsString('RuntimeException', $this->driver->messages[0]);
        $this->assertStringContainsString('ForgeLoggerTest.php', $this->driver->messages[0]);
        $this->assertStringContainsString('"trace"', $this->driver->messages[0]);
    }

    #[Test("newlines in log messages are sanitized")]
    public function log_injection_sanitization(): void
    {
        $this->logger->warning("multi\nline\r\nmessage\r");

        $this->assertCount(1, $this->driver->messages);
        $this->assertStringContainsString('[WARNING] multi line message ', $this->driver->messages[0]);
        $this->assertStringNotContainsString("\n", $this->driver->messages[0]);
    }

    #[Test("FileDriver writes to disk")]
    public function file_driver_writes_to_disk(): void
    {
        $path = '/tmp/forge-test-file-' . uniqid() . '.log';
        $driver = new FileDriver($path);

        $driver->write('[2024-01-01 00:00:00] [INFO] test');

        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('[INFO] test', $content);

        unlink($path);
    }

    #[Test("FileDriver rotates when max file size exceeded")]
    public function file_driver_rotation(): void
    {
        $path = '/tmp/forge-test-rotate-' . uniqid() . '.log';
        $driver = new FileDriver($path, maxFileSize: 50);

        $driver->write(str_repeat('A', 100));
        $this->assertFileExists($path);
        $this->assertFileDoesNotExist($path . '.1');

        $driver->write(str_repeat('B', 100));
        $this->assertFileExists($path . '.1');

        $rotated = file_get_contents($path . '.1');
        $this->assertStringContainsString(str_repeat('A', 100), $rotated);

        $current = file_get_contents($path);
        $this->assertStringContainsString(str_repeat('B', 100), $current);

        unlink($path);
        unlink($path . '.1');
    }
}

final class ArrayDriver implements LogDriverInterface
{
    public array $messages = [];

    public function write(string $message): void
    {
        $this->messages[] = $message;
    }
}
