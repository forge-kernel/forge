<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Traits;

use Modules\ForgeRouter\Http\ApiResponse;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;

trait ResponseHelper
{
    protected function createErrorResponse(Request $request, string $errorMessage = 'Too Many Requests', int $statusCode = 429): Response
    {
        if ($request->getHeader('Accept') === 'application/json') {
            return new ApiResponse(['error' => $errorMessage], $statusCode);
        }
        return new Response($errorMessage, $statusCode);
    }

    protected function createResponse(Request $request, mixed $content, int $statusCode = 200): Response
    {
        if ($request->getHeader('Accept') === 'application/json') {
            return new ApiResponse(['data' => $content], $statusCode);
        }
        return (new Response($content, $statusCode))->setHeader('Content-Type', 'text/html');
    }

    protected function jsonResponse(array $data, int $statusCode = 200): Response
    {
        $jsonData = json_encode($data);
        return (new Response($jsonData, $statusCode))->setHeader(
            "Content-Type",
            "application/json"
        );
    }

    protected function apiResponse(mixed $data, int $statusCode = 200, array $headers = []): ApiResponse
    {
        return new ApiResponse($data, $statusCode, $headers);
    }

    protected function apiError(string $message, int $statusCode = 400, array $errors = [], string $code = "ERROR_CODE"): ApiResponse
    {
        return new ApiResponse(
            null,
            $statusCode,
            [],
            [
                "error" => [
                    "code" => $code,
                    "message" => $message,
                    "errors" => $errors,
                ],
            ]
        );
    }

    protected function csvResponse(array $data, string $filename = "export.csv"): Response
    {
        $csv = $this->arrayToCsv($data);
        return (new Response($csv))
            ->setHeader("Content-Type", "text/csv")
            ->setHeader(
                "Content-Disposition",
                "attachment; filename=\"$filename\""
            );
    }

    private function arrayToCsv(array $data): string
    {
        $output = fopen("php://temp", "r+");
        foreach ($data as $row) {
            fputcsv($output, $row, ',', '"', '\\');
        }
        rewind($output);
        return stream_get_contents($output);
    }
}
