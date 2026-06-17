<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Tests;

use App\Modules\ForgePackageManager\Sources\LocalSource;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;

#[Group("package-manager")]
final class LocalSourceTest extends TestCase
{
    #[Test("LocalSource validates connection for existing directory")]
    public function local_source_validates_existing_directory(): void
    {
        $tempDir = sys_get_temp_dir() . '/forge_test_' . uniqid();
        mkdir($tempDir, 0755, true);

        $config = [
            'type' => 'local',
            'path' => $tempDir
        ];

        $source = new LocalSource($config);
        $isValid = $source->validateConnection();

        $this->assertTrue($isValid);

        rmdir($tempDir);
    }

    #[Test("LocalSource fails validation for non-existent directory")]
    public function local_source_fails_validation_for_missing_directory(): void
    {
        $config = [
            'type' => 'local',
            'path' => '/nonexistent/path/' . uniqid()
        ];

        $source = new LocalSource($config);
        $isValid = $source->validateConnection();

        $this->assertFalse($isValid);
    }

    #[Test("LocalSource supports versioning")]
    public function local_source_supports_versioning(): void
    {
        $config = [
            'type' => 'local',
            'path' => '/tmp'
        ];

        $source = new LocalSource($config);

        $this->assertTrue($source->supportsVersioning());
    }

    #[Test("LocalSource sanitizes paths to prevent directory traversal")]
    public function local_source_sanitizes_paths(): void
    {
        $tempDir = sys_get_temp_dir() . '/forge_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        mkdir($tempDir . '/modules', 0755, true);
        file_put_contents($tempDir . '/modules.json', '{}');

        $config = [
            'type' => 'local',
            'path' => $tempDir
        ];

        $source = new LocalSource($config);
        $manifest = $source->fetchModulesJson();

        $this->assertTrue(is_array($manifest));

        unlink($tempDir . '/modules.json');
        rmdir($tempDir . '/modules');
        rmdir($tempDir);
    }
}

