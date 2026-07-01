<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Tests;

use Forge\Core\Config\Config;
use Modules\ForgeTesting\Attributes\AfterEach;
use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;

#[Group('config')]
final class ConfigMergeTest extends TestCase
{
    private string $tempDir;
    private string $configPath;

    #[Test('module defaults should not fill explicitly empty arrays in file config')]
    public function empty_directives_not_overridden_by_module_defaults(): void
    {
        $this->createConfigFile('forge_router', [
            'csp' => [
                'enabled' => false,
                'directives' => [],
            ],
        ]);

        $config = new Config($this->configPath);
        $config->mergeModuleDefaults([
            'forge_router' => [
                'csp' => [
                    'enabled' => false,
                    'directives' => [
                        'default-src' => ["'self'"],
                        'script-src' => ["'self'", "'unsafe-inline'"],
                        'style-src' => ["'self'", "'unsafe-inline'"],
                    ],
                ],
            ],
        ]);

        $directives = $config->get('forge_router.csp.directives');
        $this->assertSame([], $directives, 'Empty directives from file config should not be filled by module defaults');
    }

    #[Test('file config values take precedence over module defaults')]
    public function file_config_overrides_module_defaults(): void
    {
        $this->createConfigFile('forge_router', [
            'csp' => [
                'enabled' => true,
                'directives' => [
                    'default-src' => ["'self'", 'https://example.com'],
                ],
            ],
        ]);

        $config = new Config($this->configPath);
        $config->mergeModuleDefaults([
            'forge_router' => [
                'csp' => [
                    'enabled' => false,
                    'directives' => [
                        'default-src' => ["'self'"],
                        'script-src' => ["'self'", "'unsafe-inline'"],
                    ],
                ],
            ],
        ]);

        $enabled = $config->get('forge_router.csp.enabled');
        $this->assertTrue($enabled, 'File config enabled=true should win over module default');

        $directives = $config->get('forge_router.csp.directives');
        $this->assertContains('https://example.com', $directives['default-src']);
        $this->assertArrayHasKey('script-src', $directives, 'script-src from module defaults fills missing sub-key within existing directives block');
    }

    #[Test('module defaults fill missing keys not present in file config')]
    public function module_defaults_fill_missing_keys(): void
    {
        $this->createConfigFile('forge_router', [
            'rate_limit' => [
                'enabled' => true,
            ],
        ]);

        $config = new Config($this->configPath);
        $config->mergeModuleDefaults([
            'forge_router' => [
                'rate_limit' => [
                    'enabled' => true,
                    'max_requests' => 40,
                    'time_window' => 60,
                ],
                'circuit_breaker' => [
                    'max_failures' => 5,
                ],
            ],
        ]);

        $maxRequests = $config->get('forge_router.rate_limit.max_requests');
        $this->assertSame(40, $maxRequests, 'Missing sub-key should be filled from module defaults');

        $maxFailures = $config->get('forge_router.circuit_breaker.max_failures');
        $this->assertSame(5, $maxFailures, 'Entirely missing section should be added from module defaults');
    }

    #[BeforeEach]
    public function setup(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/forge_config_test_' . uniqid();
        $this->configPath = $this->tempDir . '/config';
        mkdir($this->configPath, 0775, true);
    }

    #[AfterEach]
    public function tearDown(): void
    {
        $this->rmdirRecursive($this->tempDir);
    }

    private function createConfigFile(string $name, array $data): void
    {
        $content = '<?php return ' . var_export($data, true) . ';';
        file_put_contents($this->configPath . '/' . $name . '.php', $content);
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }
        rmdir($dir);
    }
}
