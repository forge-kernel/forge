<?php
declare(strict_types=1);

namespace App\Modules\ForgeRouter\Http;

use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use Forge\Exceptions\InvalidMiddlewareResponse;

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
