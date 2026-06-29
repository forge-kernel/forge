<?php

declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Middleware\Attributes\RegisterMiddleware;
use Forge\Exceptions\InvalidMiddlewareResponse;

#[Service]
#[RegisterMiddleware(group: 'global', order: 4, allowDuplicate: true, enabled: true)]
class CompressionMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        if (!$response instanceof Response) {
            throw new InvalidMiddlewareResponse();
        }

        if (isset($response->getHeaders()['Content-Encoding'])) {
            return $response;
        }

        $acceptEncoding = $request->getHeader('Accept-Encoding');
        $contentType = $response->getHeaders()['Content-Type'] ?? '';

        if (!str_contains($contentType, 'text/') && !str_contains($contentType, 'json')) {
            return $response;
        }

        $content = $response->getContent();
        if (empty($content) || $acceptEncoding === null) {
            return $response;
        }

        if (strlen($content) < 1024) {
            return $response;
        }

        if (str_contains($acceptEncoding, 'gzip')) {
            $compressedContent = gzencode($content, 6);
            if ($compressedContent !== false) {
                $response->setHeader('Content-Encoding', 'gzip');
                $response->setContent($compressedContent);
                $response->setHeader('Content-Length', (string)strlen($compressedContent));
            }
        } elseif (str_contains($acceptEncoding, 'deflate')) {
            $compressedContent = gzdeflate($content, 6);
            if ($compressedContent !== false) {
                $response->setHeader('Content-Encoding', 'deflate');
                $response->setContent($compressedContent);
                $response->setHeader('Content-Length', (string)strlen($compressedContent));
            }
        }

        return $response;
    }
}
