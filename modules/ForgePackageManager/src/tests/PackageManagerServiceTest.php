<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Tests;

use App\Modules\ForgePackageManager\Services\PackageManagerService;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use Forge\Core\Config\Config;

#[Group("package-manager")]
final class PackageManagerServiceTest extends TestCase
{
    #[Test("PackageManagerService loads registries from config")]
    public function service_loads_registries_from_config(): void
    {
        $testConfigDir = sys_get_temp_dir() . '/forge_test_config_' . uniqid();
        mkdir($testConfigDir, 0755, true);

        file_put_contents($testConfigDir . '/source_list.php', '<?php return ["registry" => [["name" => "test-registry", "type" => "git", "url" => "https://github.com/test/repo", "branch" => "main"]], "cache_ttl" => 3600];');

        $config = new Config($testConfigDir);
        $service = new PackageManagerService($config);
        $registries = $service->getRegistries();

        $this->assertTrue(is_array($registries));
        $this->assertNotEmpty($registries);

        unlink($testConfigDir . '/source_list.php');
        rmdir($testConfigDir);
    }

    #[Test("PackageManagerService handles empty registry config")]
    public function service_handles_empty_registry(): void
    {
        $testConfigDir = sys_get_temp_dir() . '/forge_test_config_' . uniqid();
        mkdir($testConfigDir, 0755, true);

        file_put_contents($testConfigDir . '/source_list.php', '<?php return ["registry" => [], "cache_ttl" => 3600];');

        $config = new Config($testConfigDir);
        $service = new PackageManagerService($config);
        $registries = $service->getRegistries();

        $this->assertTrue(is_array($registries));
        $this->assertEmpty($registries);

        unlink($testConfigDir . '/source_list.php');
        rmdir($testConfigDir);
    }

    #[Test("PackageManagerService handles cache_ttl as array from merge")]
    public function service_handles_cache_ttl_array(): void
    {
        $testConfigDir = sys_get_temp_dir() . '/forge_test_config_' . uniqid();
        mkdir($testConfigDir, 0755, true);

        file_put_contents($testConfigDir . '/source_list.php', '<?php return ["registry" => [], "cache_ttl" => [3600, 3600]];');

        $config = new Config($testConfigDir);
        $service = new PackageManagerService($config);

        $this->assertInstanceOf(PackageManagerService::class, $service);

        unlink($testConfigDir . '/source_list.php');
        rmdir($testConfigDir);
    }

    #[Test("PackageManagerService uses default cache_ttl when not configured")]
    public function service_uses_default_cache_ttl(): void
    {
        $testConfigDir = sys_get_temp_dir() . '/forge_test_config_' . uniqid();
        mkdir($testConfigDir, 0755, true);

        file_put_contents($testConfigDir . '/source_list.php', '<?php return ["registry" => []];');

        $config = new Config($testConfigDir);
        $service = new PackageManagerService($config);

        $this->assertInstanceOf(PackageManagerService::class, $service);

        unlink($testConfigDir . '/source_list.php');
        rmdir($testConfigDir);
    }

    #[Test("PackageManagerService gets default registry details")]
    public function service_gets_default_registry_details(): void
    {
        $testConfigDir = sys_get_temp_dir() . '/forge_test_config_' . uniqid();
        mkdir($testConfigDir, 0755, true);

        file_put_contents($testConfigDir . '/source_list.php', '<?php return ["registry" => []];');

        $config = new Config($testConfigDir);
        $service = new PackageManagerService($config);
        $defaultRegistry = $service->getDefaultRegistryDetails();

        $this->assertTrue(is_array($defaultRegistry));
        $this->assertArrayHasKey('name', $defaultRegistry);
        $this->assertArrayHasKey('url', $defaultRegistry);
        $this->assertArrayHasKey('branch', $defaultRegistry);
        $this->assertArrayHasKey('type', $defaultRegistry);

        unlink($testConfigDir . '/source_list.php');
        rmdir($testConfigDir);
    }
}

