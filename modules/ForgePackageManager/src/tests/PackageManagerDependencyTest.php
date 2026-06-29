<?php

declare(strict_types=1);

namespace Modules\ForgePackageManager\Tests;

use Modules\ForgePackageManager\Services\ConfigGeneratorService;
use Modules\ForgePackageManager\Services\PackageManagerService;
use Modules\ForgeTesting\Attributes\AfterEach;
use Modules\ForgeTesting\Attributes\BeforeEach;
use Modules\ForgeTesting\Attributes\Group;
use Modules\ForgeTesting\Attributes\Test;
use Modules\ForgeTesting\TestCase;
use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\Requires;
use ReflectionMethod;
use ReflectionProperty;

#[Group("package-manager")]
final class PackageManagerDependencyTest extends TestCase
{
    private string $testDir;
    private string $modulesPath;
    private PackageManagerService $service;

    #[BeforeEach]
    public function setup(): void
    {
        $this->testDir = sys_get_temp_dir() . '/forge_test_pkg_dep_' . uniqid();
        mkdir($this->testDir, 0755, true);

        $this->modulesPath = $this->testDir . '/modules/';
        mkdir($this->modulesPath, 0755, true);

        $cachePath = $this->testDir . '/cache/';
        mkdir($cachePath, 0755, true);

        $configDir = $this->testDir . '/config';
        mkdir($configDir, 0755, true);
        file_put_contents(
            $configDir . '/source_list.php',
            '<?php return ["registry" => [], "cache_ttl" => 3600];',
        );

        $config = new Config($configDir);
        $configGenerator = new ConfigGeneratorService();
        $this->service = new PackageManagerService($config, $configGenerator);

        $modulesProp = new ReflectionProperty(PackageManagerService::class, 'modulesPath');
        $modulesProp->setAccessible(true);
        $modulesProp->setValue($this->service, $this->modulesPath);

        $cacheProp = new ReflectionProperty(PackageManagerService::class, 'cachePath');
        $cacheProp->setAccessible(true);
        $cacheProp->setValue($this->service, $cachePath);
    }

    #[AfterEach]
    public function tearDown(): void
    {
        $this->removeDirectory($this->testDir);
    }

    private function toPascalCase(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path)
                ? $this->removeDirectory($path)
                : unlink($path);
        }
        rmdir($dir);
    }

    private function createStagingModule(
        string $stagingDir,
        string $moduleShortName,
        string $moduleFullName,
        array $requires = [],
    ): string {
        $srcPath = $stagingDir . '/' . $moduleShortName . '/src';
        mkdir($srcPath, 0755, true);

        $attrs = "#[Module(name: \"{$moduleFullName}\")]\n";
        foreach ($requires as $require) {
            $attrs .= "#[Requires(module: \"{$require}\")]\n";
        }

        $classContent = <<<PHP
<?php
namespace Modules\\{$moduleShortName};

use Forge\\Core\\Module\\Attributes\\Module;
use Forge\\Core\\Module\\Attributes\\Requires;

{$attrs}final class {$moduleShortName}Module
{
}
PHP;
        file_put_contents($srcPath . "/{$moduleShortName}Module.php", $classContent);

        return $stagingDir . '/' . $moduleShortName;
    }

    #[Test("isModuleInstalled returns true when module directory exists")]
    public function is_module_installed_returns_true(): void
    {
        $moduleName = 'test-dep-installed';
        $moduleDir = $this->modulesPath . $this->toPascalCase($moduleName);
        mkdir($moduleDir, 0755, true);

        $method = new ReflectionMethod(PackageManagerService::class, 'isModuleInstalled');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->service, $moduleName));
    }

    #[Test("isModuleInstalled returns false when module directory does not exist")]
    public function is_module_installed_returns_false(): void
    {
        $method = new ReflectionMethod(PackageManagerService::class, 'isModuleInstalled');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->service, 'non-existent-module'));
    }

    #[Test("resolveModuleDependencies returns cleanly for module with no requires")]
    public function resolves_no_dependencies(): void
    {
        $stagingBase = $this->testDir . '/staging';
        mkdir($stagingBase, 0755, true);

        $this->createStagingModule($stagingBase, 'NoDepsModule', 'NoDepsModule');

        $method = new ReflectionMethod(PackageManagerService::class, 'resolveModuleDependencies');
        $method->setAccessible(true);

        $method->invoke($this->service, $stagingBase . '/NoDepsModule', 'NoDepsModule');
        $this->assertTrue(true);
    }

    #[Test("resolveModuleDependencies skips already installed dependencies")]
    public function resolves_skip_installed_dependency(): void
    {
        $depName = 'existing-dep';
        $depDir = $this->modulesPath . $this->toPascalCase($depName);
        mkdir($depDir, 0755, true);

        $stagingBase = $this->testDir . '/staging';
        mkdir($stagingBase, 0755, true);

        $this->createStagingModule(
            $stagingBase,
            'WithExistingDep',
            'WithExistingDep',
            [$depName],
        );

        $method = new ReflectionMethod(PackageManagerService::class, 'resolveModuleDependencies');
        $method->setAccessible(true);

        $method->invoke($this->service, $stagingBase . '/WithExistingDep', 'WithExistingDep');
        $this->assertTrue(true);
    }

    #[Test("resolveModuleDependencies throws on circular dependency")]
    public function resolves_detects_circular_dependency(): void
    {
        $depName = 'module-a';

        $stagingBase = $this->testDir . '/staging';
        mkdir($stagingBase, 0755, true);

        $this->createStagingModule(
            $stagingBase,
            'DependsOnA',
            'DependsOnA',
            [$depName],
        );

        $resolvingProp = new ReflectionProperty(PackageManagerService::class, 'resolvingDependencies');
        $resolvingProp->setAccessible(true);
        $resolvingProp->setValue($this->service, [$depName]);

        $method = new ReflectionMethod(PackageManagerService::class, 'resolveModuleDependencies');
        $method->setAccessible(true);

        $threw = false;
        try {
            $method->invoke($this->service, $stagingBase . '/DependsOnA', 'DependsOnA');
        } catch (\RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('Circular dependency', $e->getMessage());
            $this->assertStringContainsString($depName, $e->getMessage());
        }
        $this->assertTrue($threw);
    }

    #[Test("Requires attribute accepts module parameter")]
    public function requires_attribute_accepts_module_parameter(): void
    {
        $requires = new Requires(module: 'forge-router', version: '>=1.0.0');
        $this->assertSame('forge-router', $requires->module);
        $this->assertSame('>=1.0.0', $requires->version);
        $this->assertNull($requires->interface);
    }

    #[Test("Requires attribute accepts interface parameter")]
    public function requires_attribute_accepts_interface_parameter(): void
    {
        $requires = new Requires(interface: 'SomeInterface', version: '>=0.1.0');
        $this->assertSame('SomeInterface', $requires->interface);
        $this->assertSame('>=0.1.0', $requires->version);
        $this->assertNull($requires->module);
    }

    #[Test("Requires attribute accepts both module and interface")]
    public function requires_attribute_accepts_both(): void
    {
        $requires = new Requires(
            interface: 'SomeInterface',
            module: 'some-module',
            version: '>=1.0.0',
        );
        $this->assertSame('SomeInterface', $requires->interface);
        $this->assertSame('some-module', $requires->module);
        $this->assertSame('>=1.0.0', $requires->version);
    }

    #[Test("Requires attribute throws when neither module nor interface given")]
    public function requires_attribute_throws_when_empty(): void
    {
        $threw = false;
        try {
            new Requires();
        } catch (\InvalidArgumentException $e) {
            $threw = true;
            $this->assertStringContainsString(
                'Requires attribute must specify at least one of "interface" or "module"',
                $e->getMessage(),
            );
        }
        $this->assertTrue($threw);
    }
}
