<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Traits;

use App\Modules\ForgeRouter\Http\ApiResponse;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;

trait ResponseHelper
{
    private function createErrorResponse(Request $request, string $errorMessage = 'Too Many Requests', int $statusCode = 429): Response
    {
        if ($request->getHeader('Accept') === 'application/json') {
            return new ApiResponse(['error' => $errorMessage], $statusCode);
        }
        return new Response($errorMessage, $statusCode);
    }

    private function createResponse(Request $request, mixed $content, int $statusCode = 200): Response
    {
        if ($request->getHeader('Accept') === 'application/json') {
            return new ApiResponse(['data' => $content], $statusCode);
        }
        return (new Response($content, $statusCode))->setHeader('Content-Type', 'text/html');
    }
}
