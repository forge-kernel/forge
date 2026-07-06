<?php
declare(strict_types=1);

namespace Modules\ForgeRouter\Routing;

use FilesystemIterator;
use Forge\Core\DI\Container;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final readonly class ControllerLoader
{
  /**
   * @param array<int, array{path: string, namespace: string}> $controllerDirs
   */
  public function __construct(
    private Container $container,
    private array $controllerDirs = [],
  ) {
  }

  public function registerControllers(): array
  {
    $registeredControllers = [];
    foreach ($this->controllerDirs as $dirInfo) {
      $dir = $dirInfo['path'];
      $namespace = $dirInfo['namespace'];

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

        $path = $file->getPathname();

        $relative = substr($path, strlen($dir) + 1);
        $class = $namespace . '\\' . str_replace(['/', '.php'], ['\\', ''], $relative);

        if (!class_exists($class)) {
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
}
