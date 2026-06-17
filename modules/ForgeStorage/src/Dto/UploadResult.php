<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Dto;

final class UploadResult
{
  public function __construct(
    public readonly string $path,
    public readonly string $url,
    public readonly int $size,
    public readonly string $mimeType,
    public readonly string $originalName
  ) {
  }

  public function toArray(): array
  {
    return [
      'path' => $this->path,
      'url' => $this->url,
      'size' => $this->size,
      'mime_type' => $this->mimeType,
      'original_name' => $this->originalName,
    ];
  }
}
