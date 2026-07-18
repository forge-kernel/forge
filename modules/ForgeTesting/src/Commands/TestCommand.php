<?php

declare(strict_types=1);

namespace Modules\ForgeTesting\Commands;

use Forge\CLI\Attributes\CoreCommand;
use Modules\ForgeTesting\Services\TestRunnerService;
use Forge\CLI\Command;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Structure\StructureResolver;
use Forge\Traits\NamespaceHelper;

#[CoreCommand]
#[Cli(
    command: 'test',
    description: 'Run application tests',
    usage: 'test [--type=TYPE] [--module=MODULE] [--group=GROUP]',
    examples: [
        'test',
        'test --type=module --module=users',
        'test --group=unit',
        'test --type=kernel'
    ]
)]
final class TestCommand extends Command
{
    use OutputHelper;
    use Wizard;
    use NamespaceHelper;

    private const CACHE_FILE = BASE_PATH . '/storage/framework/cache/test_cache.php';
    private const CACHE_TTL = 3600;

    #[Arg(
        name: 'type',
        description: 'Type of tests: app, kernel, module',
        default: 'app',
        required: false
    )]
    private string $type;

    #[Arg(
        name: 'module',
        description: 'Module(s) to test (default: all)',
        default: 'all',
        required: false
    )]
    private string|array $module;

    #[Arg(
        name: 'group',
        description: 'Filter tests by group (optional)',
        required: false
    )]
    private ?string $group = null;

    public function __construct(private readonly TestRunnerService $testRunnerService)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        $startTime = microtime(true);

        $testDirs = $this->getTestDirectories($this->type, $this->module);
        if (empty($testDirs)) {
            $this->error('No test directories found');
            return 1;
        }

        $cache = $this->getValidatedCache($testDirs);

        $this->testRunnerService
            ->setTestClasses($cache['classes'])
            ->setGroupFilter($this->group);

        $this->info("Running tests...\n");
        $results = $this->testRunnerService->runTests();

        $this->updateCache($cache['meta'], $testDirs);
        $this->renderExecutionTime($startTime);

        return $results['failed'] > 0 ? 1 : 0;
    }

    private function getTestDirectories(string $type, string|array $module): array
    {
        $container = \Forge\Core\DI\Container::getInstance();
        $structureResolver = $container->has(\Forge\Core\Structure\StructureResolver::class)
            ? $container->get(\Forge\Core\Structure\StructureResolver::class)
            : null;

        return match ($type) {
            'app' => $this->getAppTestDir($structureResolver),
            'kernel' => [BASE_PATH . '/kernel/tests/'],
            'module' => $this->getModuleTestDirs($module, $structureResolver),
            default => [],
        };
    }

    private function getAppTestDir(?\Forge\Core\Structure\StructureResolver $structureResolver): array
    {
        if ($structureResolver) {
            try {
                $appTestsPath = $structureResolver->getAppPath('tests');
                return [BASE_PATH . '/' . $appTestsPath];
            } catch (\InvalidArgumentException $e) {
                return [BASE_PATH . '/app/tests/'];
            }
        }
        return [BASE_PATH . '/app/tests/'];
    }

    private function getModuleTestDirs(string|array $module, ?\Forge\Core\Structure\StructureResolver $structureResolver): array
    {
        $dirs = [];
        $modules = is_array($module) ? $module : [$module];

        foreach ($modules as $moduleName) {
            $pascalCase = $this->kebabToPascal($moduleName);
            $found = false;

            foreach (StructureResolver::resolveModulesRoots() as $root) {
                $modulesPath = BASE_PATH . '/' . $root;

                if ($structureResolver) {
                    try {
                        $moduleTestsPath = $structureResolver->getModulePath($pascalCase, 'tests');
                        $path = "{$modulesPath}/{$pascalCase}/{$moduleTestsPath}";
                    } catch (\InvalidArgumentException $e) {
                        $path = "{$modulesPath}/{$pascalCase}/src/tests/";
                    }
                } else {
                    $path = "{$modulesPath}/{$pascalCase}/src/tests/";
                }

                if (is_dir($path)) {
                    $dirs[] = $path;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $dirs[] = BASE_PATH . "/{$pascalCase}/src/tests/";
            }
        }

        return $dirs;
    }

    private function kebabToPascal(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $name)));
    }

    private function getValidatedCache(array $testDirs): array
    {
        $currentHashes = $this->getDirectoryHashes($testDirs);
        return [
            'meta' => ['hashes' => $currentHashes, 'timestamp' => time()],
            'classes' => $this->scanTestClasses($testDirs)
        ];
    }

    private function getDirectoryHashes(array $directories): array
    {
        $hashes = [];
        foreach ($directories as $dir) {
            $hash = '';
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file) {
                if ($file->isFile())
                    $hash .= md5_file($file->getRealPath());
            }
            $hashes[$dir] = md5($hash);
        }
        return $hashes;
    }

    private function scanTestClasses(array $directories): array
    {
        $classes = [];
        $loadingFiles = [];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
                    $filePath = $file->getRealPath();

                    if (isset($loadingFiles[$filePath])) {
                        continue;
                    }

                    $loadingFiles[$filePath] = true;

                    try {
                        $className = $this->getClassNameFromFile($filePath);

                        if (!$className) {
                            require_once $filePath;
                            $declaredClasses = get_declared_classes();
                            foreach ($declaredClasses as $declaredClass) {
                                try {
                                    $reflection = new \ReflectionClass($declaredClass);
                                    if ($reflection->getFileName() === $filePath && str_ends_with($declaredClass, 'Test')) {
                                        $className = $declaredClass;
                                        break;
                                    }
                                } catch (\Throwable $e) {
                                    continue;
                                }
                            }
                        } else {
                            if (class_exists($className, false)) {
                                $classes[] = $className;
                                continue;
                            }

                            require_once $filePath;
                        }

                        if ($className && class_exists($className, false)) {
                            $classes[] = $className;
                        }
                    } catch (\Throwable $e) {
                        continue;
                    } finally {
                        unset($loadingFiles[$filePath]);
                    }
                }
            }
        }

        return array_unique($classes);
    }

    private function updateCache(array $meta, array $testDirs): void
    {
        $cacheContent = "<?php\n\nreturn " . var_export([
            'meta' => $meta,
            'classes' => $this->scanTestClasses($testDirs)
        ], true) . ';';

        file_put_contents(self::CACHE_FILE, $cacheContent);
    }

    private function renderExecutionTime(float $startTime): void
    {
        $duration = number_format(microtime(true) - $startTime, 2);
        $this->comment("\nTests completed in {$duration}s");
    }

    private function getAllModules(): array
    {
        $modules = [];
        foreach (StructureResolver::resolveModulesRoots() as $root) {
            $modulesPath = BASE_PATH . '/' . $root . '/';
            foreach (new \DirectoryIterator($modulesPath) as $fileInfo) {
                if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                    $modules[] = $this->pascalToKebab($fileInfo->getFilename());
                }
            }
        }
        return $modules;
    }

    private function pascalToKebab(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
    }
}
