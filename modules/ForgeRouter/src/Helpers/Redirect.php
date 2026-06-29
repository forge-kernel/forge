<?php

declare(strict_types=1);

namespace Modules\ForgeRouter\Helpers;

use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;

final class Redirect
{
    /**
     * Create a redirect response
     *
     * @param string $url Uri to redirect to
     * @param int $status Http status code for redirect default to 302 found
     * @param array<string, string> $headers Additional headers.
     * @return Response
     */
    public static function to(string $uri, int $status = 302, array $headers = []): Response
    {
        return (new Response("", $status, array_merge($headers, ['Location' => $uri])));
    }

    /**
     * Create a redirect response to the back url referer header
     *
     * @param array<string, string> $headers additional headers
     * @return Response
     */
    public static function back(Request $request, array $headers = []): Response
    {
        $referer = $request->getHeader('Referer');
        $uri = $referer ?: '/';

        $response = (new Response(""))->setStatusCode(302);
        $response->setHeader('Location', $uri);
        if (!empty($headers)) {
            foreach ($headers as $key => $value) {
                $response->setHeader($key, $value);
            }
        }
        return $response;
    }
}
