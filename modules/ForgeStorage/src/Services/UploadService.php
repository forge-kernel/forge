<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Services;

use App\Modules\ForgeStorage\Contracts\StorageDriverInterface;
use App\Modules\ForgeStorage\Dto\UploadResult;
use App\Modules\ForgeStorage\Validators\FileValidator;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\UUID;
use App\Modules\ForgeRouter\Http\UploadedFile;

#[Service]
final class UploadService
{
  public function __construct(
    private StorageDriverInterface $driver,
    private FileValidator $validator,
    private Config $config
  ) {
  }

  public function upload(UploadedFile|array $files, ?string $location = null): UploadResult|array
  {
    if (is_array($files)) {
      return $this->uploadMultiple($files, $location);
    }

    return $this->uploadSingle($files, $location);
  }

  private function uploadSingle(UploadedFile $file, ?string $location): UploadResult
  {
    $this->validator->validate($file, $location);

    $locationConfig = $this->getLocationConfig($location);
    $hashFilenames = $this->config->get('forge_storage.hash_filenames', true);

    $originalName = $file->getClientFilename();
    $filename = $hashFilenames ? UUID::generate() : $this->sanitizeFilename($originalName);
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    if ($extension) {
      $filename .= '.' . $extension;
    }

    $basePath = 'uploads';
    $path = $location ? "{$basePath}/{$location}/{$filename}" : "{$basePath}/{$filename}";

    $contents = stream_get_contents($file->getStream());
    if (!$this->driver->put($path, $contents)) {
      throw new \RuntimeException('Failed to upload file');
    }

    $url = $this->driver->getUrl($path);

    return new UploadResult(
      path: $path,
      url: $url,
      size: $file->getSize(),
      mimeType: $file->getClientMediaType(),
      originalName: $originalName
    );
  }

  private function uploadMultiple(array $files, ?string $location): array
  {
    $results = [];
    foreach ($files as $file) {
      if ($file instanceof UploadedFile) {
        $results[] = $this->uploadSingle($file, $location);
      }
    }
    return $results;
  }

  private function getLocationConfig(?string $location): array
  {
    if ($location === null) {
      return [];
    }

    $locations = $this->config->get('forge_storage.locations', []);
    return $locations[$location] ?? [];
  }

  private function sanitizeFilename(string $filename): string
  {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $name = preg_replace('/-+/', '-', $name);
    $name = trim($name, '-');
    return $extension ? "{$name}.{$extension}" : $name;
  }
}
