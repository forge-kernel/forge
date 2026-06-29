<?php
declare(strict_types=1);

namespace Modules\ForgeRouter\Routing;

use FilesystemIterator;
use Forge\Core\DI\Container;
use Forge\Core\Helpers\ModuleHelper;
use Forge\Core\Structure\StructureResolver;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final readonly class ControllerLoader
{
  public function __construct(
    private Container $container,
    private array $controllerDirs = [],
    private ?StructureResolver $structureResolver = null
  ) {
  }

  public function registerControllers(): array
  {
    $registeredControllers = [];
    foreach ($this->controllerDirs as $dir) {
      if (!is_dir($dir)) {
        continue;
      }

      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
      );

      foreach ($iterator as $file) {
        if (!$file->isFile()) {
          continue;
        }

        if ($file->getExtension() !== 'php') {
          continue;
        }

        if (!str_contains($file->getFilename(), 'Controller')) {
          continue;
        }

        $path = $file->getPathname();
        $class = $this->fileToClass($path, $dir);
        if (!$class) {
          continue;
        }

        $this->container->register($class);
        $registeredControllers[$class] = [
          'class' => $class,
          'file' => $path,
          'mtime' => $file->getMTime(),
        ];
      }
    }

    return array_values($registeredControllers);
  }

  private function fileToClass(string $file, string $baseDir): ?string
  {
    $relativePath = str_replace($baseDir, '', $file);
    $class = str_replace(['/', '.php'], ['\\', ''], trim($relativePath, '/'));

    if ($this->structureResolver) {
      $appControllersPath = BASE_PATH . '/' . $this->structureResolver->getAppPath('controllers');
      if (str_starts_with($baseDir, $appControllersPath)) {
        $structurePath = $this->structureResolver->getAppPath('controllers');
        $relativeFromApp = str_replace('app/', '', $structurePath);
        $namespaceParts = explode('/', $relativeFromApp);
        $namespaceParts = array_filter($namespaceParts);
        $namespaceParts = array_map(fn($part) => ucfirst($part), $namespaceParts);
        $namespacePrefix = 'App\\' . implode('\\', $namespaceParts);
        return "$namespacePrefix\\$class";
      }

      $modulesPath = BASE_PATH . '/modules';
      if (str_starts_with($baseDir, $modulesPath)) {
        $relativeToModules = str_replace($modulesPath . '/', '', $baseDir);
        $parts = explode('/', $relativeToModules);
        if (!empty($parts)) {
          $moduleName = $parts[0];
          if (ModuleHelper::isModuleDisabled($moduleName)) {
            return null;
          }
          try {
            $moduleControllersPath = $this->structureResolver->getModulePath($moduleName, 'controllers');
            $expectedPath = "$modulesPath/$moduleName/$moduleControllersPath";
            if (str_starts_with($baseDir, $expectedPath)) {
              $namespacePath = preg_replace('#^src/#', '', $moduleControllersPath);
              $namespaceParts = explode('/', $namespacePath);
              $namespaceParts = array_filter($namespaceParts);
              $namespaceParts = array_map(fn($part) => ucfirst($part), $namespaceParts);
              $namespacePrefix = "Modules\\{$moduleName}\\" . implode('\\', $namespaceParts);
              return "$namespacePrefix\\$class";
            }
          } catch (\InvalidArgumentException $e) {
            return null;
          }
        }
      }
    } else {
      if (str_starts_with($baseDir, BASE_PATH . "/app/Controllers")) {
        return "App\\Controllers\\$class";
      }
      if (preg_match('#modules/([^/]+)/src/Controllers#', $baseDir, $matches)) {
        $moduleName = $matches[1];
        if (ModuleHelper::isModuleDisabled($moduleName)) {
          return null;
        }
        return "Modules\\{$moduleName}\\Controllers\\$class";
      }
    }

    throw new \RuntimeException("Invalid controller path: $file");
  }
}
