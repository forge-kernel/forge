<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Middlewares;

use App\Modules\ForgeAuth\Exceptions\JwtTokenExpiredException;
use App\Modules\ForgeAuth\Exceptions\JwtTokenInvalidException;
use App\Modules\ForgeAuth\Exceptions\JwtTokenMissingException;
use App\Modules\ForgeAuth\Contracts\UserContextInterface;
use App\Modules\ForgeAuth\Services\TokenManagerService;
use Forge\Core\DI\Attributes\Service;
use App\Modules\ForgeRouter\Http\ApiResponse;
use App\Modules\ForgeRouter\Http\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;

#[Service]
final class ApiJwtMiddleware extends Middleware
{
    public function __construct(
        private readonly TokenManagerService $tokenManagerService,
        private readonly UserContextInterface $userContext
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $authHeader = $request->getHeader('Authorization');
        if (!$authHeader) {
            return $this->unauthorizedResponse('Unauthorized: Missing token');
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorizedResponse('Unauthorized: Invalid token');
        }

        $token = substr($authHeader, 7);
        if (empty($token)) {
            return $this->unauthorizedResponse('Unauthorized: Missing token');
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return $this->unauthorizedResponse('Unauthorized: Invalid token');
        }

        try {
            $user = $this->tokenManagerService->resolveUserFromToken($token);
        } catch (JwtTokenMissingException | JwtTokenInvalidException | JwtTokenExpiredException $e) {
            return $this->unauthorizedResponse($e->getMessage());
        }

        if (!$user) {
            return $this->unauthorizedResponse('Unauthorized: Invalid token');
        }

        $this->userContext->setCurrentUser($user);

        return $next($request);
    }

    private function unauthorizedResponse(string $message): Response
    {
        return new ApiResponse(
            null,
            401,
            [],
            [
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => $message,
                    'errors' => [],
                ],
            ]
        );
    }
}
