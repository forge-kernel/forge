<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Http\Middlewares;

use Forge\Core\Helpers\FileExistenceCache;
use Modules\ForgeRouter\Http\Middleware as MiddlewareExtend;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Middleware\Attributes\Middleware;

/**
 * HTTP caching middleware with ETag and conditional request support.
 *
 * This middleware implements proper HTTP caching strategies including:
 * - ETag generation and validation
 * - Last-Modified handling
 * - Conditional GET/HEAD requests
 * - Cache control headers
 * - Vary header support
 */
#[Middleware(group: 'global', order: 5, allowDuplicate: true, enabled: true)]
class HttpCacheMiddleware extends MiddlewareExtend
{
    private const int DEFAULT_MAX_AGE = 3600; // 1 hour
    private const string ETAG_ALGORITHM = 'xxh3'; // Fast hash algorithm

    /**
     * Handle HTTP caching for requests and responses.
     */
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        // Don't cache error responses
        if ($response->getStatusCode() >= 400) {
            $this->addNoCacheHeaders($response);
            return $response;
        }

        // Handle conditional requests
        $conditionalResponse = $this->handleConditionalRequest($request, $response);
        if ($conditionalResponse !== null) {
            return $conditionalResponse;
        }

        // Add caching headers to response
        $this->addCacheHeaders($request, $response);

        return $response;
    }

    /**
     * Handle conditional requests (If-Match, If-None-Match, If-Modified-Since).
     *
     * @param Request $request The request
     * @param Response $response The potential response
     * @return Response|null Conditional response or null
     */
    private function handleConditionalRequest(Request $request, Response $response): ?Response
    {
        $ifMatch = $request->getHeader('If-Match');
        $ifNoneMatch = $request->getHeader('If-None-Match');
        $ifModifiedSince = $request->getHeader('If-Modified-Since');

        // Generate ETag for current response
        $etag = $this->generateETag($response);
        $lastModified = $this->getLastModified($response);

        // Handle If-Match (for PUT/PATCH/DELETE)
        if ($ifMatch !== null && !$this->matchesETag($etag, $ifMatch)) {
            return new Response('Precondition Failed', 412);
        }

        // Handle If-None-Match (for GET/HEAD)
        if ($ifNoneMatch !== null) {
            if ($this->matchesETag($etag, $ifNoneMatch)) {
                // Not Modified
                return $this->createNotModifiedResponse($etag, $lastModified);
            }
        }

        // Handle If-Modified-Since
        if ($ifModifiedSince !== null && $lastModified !== null) {
            $ifModifiedSinceTime = strtotime($ifModifiedSince);
            $lastModifiedTime = strtotime($lastModified);

            if ($ifModifiedSinceTime >= $lastModifiedTime) {
                return $this->createNotModifiedResponse($etag, $lastModified);
            }
        }

        return null;
    }

    /**
     * Add appropriate caching headers to response.
     *
     * @param Request $request The request
     * @param Response $response The response
     */
    private function addCacheHeaders(Request $request, Response $response): void
    {
        $contentType = $response->getHeader('Content-Type') ?? '';
        $statusCode = $response->getStatusCode();

        // Don't cache certain content types by default
        if ($this->shouldNotCache($contentType, $statusCode)) {
            $this->addNoCacheHeaders($response);
            return;
        }

        // Generate ETag
        $etag = $this->generateETag($response);
        $response->setHeader('ETag', '"' . $etag . '"');

        // Add Last-Modified if applicable
        $lastModified = $this->getLastModified($response);
        if ($lastModified !== null) {
            $response->setHeader('Last-Modified', $lastModified);
        }

        // Add Cache-Control based on content type and status
        $maxAge = $this->determineMaxAge($contentType, $statusCode);
        $cacheControl = "public, max-age={$maxAge}";

        // Add must-revalidate for dynamic content
        if ($this->isDynamicContent($contentType)) {
            $cacheControl .= ', must-revalidate';
        }

        $response->setHeader('Cache-Control', $cacheControl);

        // Add Vary header for content negotiation
        $varyHeaders = $this->determineVaryHeaders($request);
        if (!empty($varyHeaders)) {
            $response->setHeader('Vary', implode(', ', $varyHeaders));
        }

        // Add Expires header for older clients
        $expires = gmdate('D, d M Y H:i:s \G\M\T', time() + $maxAge);
        $response->setHeader('Expires', $expires);
    }

    /**
     * Add no-cache headers to response.
     *
     * @param Response $response The response
     */
    private function addNoCacheHeaders(Response $response): void
    {
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Expires', '0');
    }

    /**
     * Generate ETag for response content.
     *
     * @param Response $response The response
     * @return string ETag value
     */
    private function generateETag(Response $response): string
    {
        $content = $response->getContent();
        $headers = $response->getHeaders();

        // Include content and relevant headers in hash
        $etagData = [
            'content' => $content,
            'content_type' => $headers['Content-Type'] ?? '',
            'content_length' => strlen($content),
            'status' => $response->getStatusCode(),
        ];

        return hash(self::ETAG_ALGORITHM, serialize($etagData));
    }

    /**
     * Get Last-Modified date for response.
     *
     * @param Response $response The response
     * @return string|null Last-Modified date or null
     */
    private function getLastModified(Response $response): ?string
    {
        // Check if already set
        $existing = $response->getHeader('Last-Modified');
        if ($existing !== null) {
            return $existing;
        }

        // Generate based on content type and data
        $content = $response->getContent();
        if (empty($content)) {
            return null;
        }

        // For file-based content, use file modification time
        $filePath = $response->getHeader('X-File-Path');
        if ($filePath !== null && FileExistenceCache::exists($filePath)) {
            $fileMtime = FileExistenceCache::getMtime($filePath);
            return gmdate('D, d M Y H:i:s \G\M\T', $fileMtime ?? time());
        }

        // For database content, this would need to be implemented
        // For now, use current time
        return gmdate('D, d M Y H:i:s \G\M\T');
    }

    /**
     * Check if ETag matches the condition.
     *
     * @param string $etag Generated ETag
     * @param string $condition ETag condition header
     * @return bool True if matches
     */
    private function matchesETag(string $etag, string $condition): bool
    {
        // Handle wildcards
        if ($condition === '*') {
            return true;
        }

        // Parse multiple ETags
        $etags = array_map('trim', explode(',', $condition));
        $etag = '"' . $etag . '"';

        return in_array($etag, $etags) || in_array('W/' . $etag, $etags);
    }

    /**
     * Create a 304 Not Modified response.
     *
     * @param string $etag ETag value
     * @param string|null $lastModified Last-Modified date
     * @return Response 304 response
     */
    private function createNotModifiedResponse(string $etag, ?string $lastModified): Response
    {
        $response = new Response('', 304);
        $response->setHeader('ETag', '"' . $etag . '"');

        if ($lastModified !== null) {
            $response->setHeader('Last-Modified', $lastModified);
        }

        return $response;
    }

    /**
     * Determine if content should not be cached.
     *
     * @param string $contentType Content type
     * @param int $statusCode HTTP status code
     * @return bool True if should not cache
     */
    private function shouldNotCache(string $contentType, int $statusCode): bool
    {
        // Don't cache error responses
        if ($statusCode >= 400) {
            return true;
        }

        // Don't cache certain content types
        $noCacheTypes = [
            'text/html', // HTML is often dynamic
            'application/octet-stream', // Binary data
        ];

        foreach ($noCacheTypes as $type) {
            if (str_starts_with($contentType, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine maximum age for caching.
     *
     * @param string $contentType Content type
     * @param int $statusCode HTTP status code
     * @return int Max age in seconds
     */
    private function determineMaxAge(string $contentType, int $statusCode): int
    {
        // Different cache times for different content types
        $cacheTimes = [
            'text/css' => 86400,        // 1 day
            'application/javascript' => 86400, // 1 day
            'image/jpeg' => 2592000,     // 30 days
            'image/png' => 2592000,        // 30 days
            'image/gif' => 2592000,        // 30 days
            'image/svg+xml' => 2592000,   // 30 days
            'font/' => 2592000,            // 30 days
            'application/json' => 300,       // 5 minutes (API data)
        ];

        foreach ($cacheTimes as $type => $maxAge) {
            if (str_contains($contentType, $type)) {
                return $maxAge;
            }
        }

        // Default cache time
        return self::DEFAULT_MAX_AGE;
    }

    /**
     * Check if content is dynamic.
     *
     * @param string $contentType Content type
     * @return bool True if dynamic
     */
    private function isDynamicContent(string $contentType): bool
    {
        $dynamicTypes = [
            'application/json',
            'application/xml',
            'text/html',
        ];

        foreach ($dynamicTypes as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine Vary headers based on request.
     *
     * @param Request $request The request
     * @return array<string> Vary header values
     */
    private function determineVaryHeaders(Request $request): array
    {
        $vary = [];

        // Add Accept if content negotiation is used
        if ($request->getHeader('Accept') !== null) {
            $vary[] = 'Accept';
        }

        // Add Accept-Encoding if compression is used
        if ($request->getHeader('Accept-Encoding') !== null) {
            $vary[] = 'Accept-Encoding';
        }

        // Add Accept-Language if internationalization is used
        if ($request->getHeader('Accept-Language') !== null) {
            $vary[] = 'Accept-Language';
        }

        // Add Cookie if user-specific content
        if (!empty($request->cookies)) {
            $vary[] = 'Cookie';
        }

        return $vary;
    }
}
