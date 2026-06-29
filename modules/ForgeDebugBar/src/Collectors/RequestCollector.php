<?php

namespace Modules\ForgeDebugBar\Collectors;

use Modules\ForgeRouter\Http\Request;

class RequestCollector
{
    public static function collect(Request $request): array
    {
        if (!$request) {
            return ['error' => 'Request object not available'];
        }

        return [
            'url' => $request->getUrl(),
            'method' => $request->getMethod(),
            'ip' => $request->getClientIp(),
            'headers' => $request->getHeaders(),
            'query' => $request->getQuery(),
            'body' => $request->all(),
            'cookies' => $_COOKIE,
            'files' => $_FILES,
        ];
    }
}
