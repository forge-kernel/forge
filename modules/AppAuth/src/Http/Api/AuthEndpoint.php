<?php

declare(strict_types=1);

namespace Modules\AppAuth\Http\Api;

use Modules\ForgeAuth\Exceptions\LoginException;
use Modules\ForgeAuth\Services\ForgeAuthService;
use Modules\ForgeAuth\Services\TokenManagerService;
use Modules\ForgeRouter\Http\Attributes\ApiRoute;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Forge\Exceptions\ValidationException;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Forge\Traits\SecurityHelper;

#[Routable(prefix: '/auth')]
#[UseMiddleware('api')]
final class AuthEndpoint
{
    use ResponseHelper;
    use SecurityHelper;

    public function __construct(
        private readonly ForgeAuthService $forgeAuthService,
        private readonly TokenManagerService $tokenManagerService
    ) {
    }

    #[ApiRoute('/login', 'POST')]
    public function login(Request $request): Response
    {
        try {
            $data = $request->json() ?: $request->postData;
            $loginCredentials = $this->sanitize($data);

            $user = $this->forgeAuthService->login($loginCredentials);
            $tokens = $this->tokenManagerService->issueToken($user);

            $responseData = [
                'user' => $user,
                'tokens' => $tokens,
            ];

            return $this->apiResponse($responseData);
        } catch (ValidationException $e) {
            return $this->apiError('Validation failed', 422, [], 'VALIDATION_ERROR');
        } catch (LoginException $e) {
            return $this->apiError('Invalid credentials', 401);
        } catch (\RuntimeException $e) {
            return $this->apiError('JWT is not enabled', 500);
        }
    }

    #[ApiRoute('/refresh', 'POST')]
    public function refresh(Request $request): Response
    {
        $data = $request->json() ?: $request->postData;
        $refreshToken = $data['refresh_token'] ?? null;

        if (!$refreshToken) {
            return $this->apiError('Refresh token is required', 400);
        }

        $tokens = $this->tokenManagerService->refreshToken($refreshToken);

        if (!$tokens) {
            return $this->apiError('Invalid refresh token', 401);
        }

        return $this->apiResponse($tokens);
    }
}
