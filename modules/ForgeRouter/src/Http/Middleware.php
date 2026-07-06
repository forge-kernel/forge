<?php
declare(strict_types=1);

namespace Modules\ForgeRouter\Http;

use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Exceptions\InvalidMiddlewareResponse;

abstract class Middleware
{
    /**
     * @param Request $request
     * @param callable $next
     *
     * @return Response
     * @throws InvalidMiddlewareResponse
     */
    abstract public function handle(Request $request, callable $next): Response;
}
