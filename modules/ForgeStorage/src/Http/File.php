<?php

declare(strict_types=1);

namespace Modules\ForgeStorage\Http;

use Modules\ForgeStorage\Services\UploadService;
use Modules\ForgeStorage\Utils\UploadSignature;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Http\UploadedFile;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Forge\Core\Session\SessionInterface;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable]
#[UseMiddleware('web')]
final class File
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private UploadService $uploadService,
        private UploadSignature $signature,
        private SessionInterface $session
    ) {
    }

    #[Endpoint(path: '/__upload', method: 'POST')]
    public function upload(Request $request): Response
    {
        $signatureValue = $request->postData['signature'] ?? $request->query('signature');

        if (!$signatureValue) {
            return $this->jsonResponse(['error' => 'Signature required'], 400);
        }

        try {
            $stored = $this->signature->verify((string) $signatureValue, $this->session);
            $location = $stored['location'] ?? null;
        } catch (\RuntimeException $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 403);
        }

        $files = $this->getFiles($request);

        if (empty($files)) {
            return $this->jsonResponse(['error' => 'No files provided'], 400);
        }

        try {
            if (count($files) === 1) {
                $result = $this->uploadService->upload($files[0], $location);
                return $this->jsonResponse($result->toArray(), 201);
            }

            $results = $this->uploadService->upload($files, $location);
            $data = array_map(fn($r) => $r->toArray(), $results);
            return $this->jsonResponse(['files' => $data], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function getFiles(Request $request): array
    {
        $files = [];

        if ($request->hasFile('file')) {
            $file = $request->getFile('file');
            if ($file) {
                $files[] = $file;
            }
        }

        if (isset($_FILES['files'])) {
            $uploadedFiles = $_FILES['files'];
            if (is_array($uploadedFiles['error'])) {
                for ($i = 0; $i < count($uploadedFiles['error']); $i++) {
                    if ($uploadedFiles['error'][$i] === UPLOAD_ERR_OK) {
                        $fileData = [
                            'name' => $uploadedFiles['name'][$i],
                            'type' => $uploadedFiles['type'][$i],
                            'tmp_name' => $uploadedFiles['tmp_name'][$i],
                            'error' => $uploadedFiles['error'][$i],
                            'size' => $uploadedFiles['size'][$i],
                        ];
                        $files[] = new UploadedFile($fileData);
                    }
                }
            }
        }

        return $files;
    }

}
