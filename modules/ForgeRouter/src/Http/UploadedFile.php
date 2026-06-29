<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http;

use RuntimeException;

final class UploadedFile
{
    private string $clientFilename;
    private string $clientMediaType;
    private int $size;
    private string $tempPath;
    private int $error;

    public function __construct(array $fileInfo)
    {
        $this->clientFilename = $fileInfo['name'];
        $this->clientMediaType = $fileInfo['type'];
        $this->size = $fileInfo['size'];
        $this->tempPath = $fileInfo['tmp_name'];
        $this->error = $fileInfo['error'];

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(sprintf('File upload error: %s', $this->getErrorMessage($this->error)));
        }
    }

    public function getClientFilename(): string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): string
    {
        return $this->clientMediaType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getStream()
    {
        $resource = fopen($this->tempPath, 'r+');
        if ($resource === false) {
            throw new RuntimeException(sprintf('Could not open stream for uploaded file "%s"', $this->clientFilename));
        }
        return $resource;
    }

    public function getTempPath(): string
    {
        return $this->tempPath;
    }

    public function moveTo(string $targetPath): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Cannot move file due to upload error.');
        }
        if (!is_uploaded_file($this->tempPath)) {
            throw new RuntimeException(sprintf('The file "%s" was not uploaded via HTTP POST.', $this->clientFilename));
        }
        if (!rename($this->tempPath, $targetPath)) {
            throw new RuntimeException(sprintf('Could not move uploaded file "%s" to "%s".', $this->clientFilename, $targetPath));
        }
    }

    private function getErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
            default => 'Unknown upload error',
        };
    }
}
