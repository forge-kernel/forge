<?php

declare(strict_types=1);

namespace Modules\AppAuth\Http\Web;

use Modules\AppAuth\Models\User;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Forge\Exceptions\UserNotFoundException;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeRouter\Traits\PaginationHelper;

#[Routable]
#[UseMiddleware('web')]
final class UserEndpoint
{
    use ResponseHelper;
    use PaginationHelper;

    public function __construct()
    {
    }

    #[Endpoint('/users')]
    public function index(Request $request): Response
    {
        $paginationParams = $this->getPaginationParams($request);

        $result = User::paginate(
            $paginationParams['page'],
            $paginationParams['limit'],
            $paginationParams['column'],
            $paginationParams['direction'],
            $paginationParams['search']
        );

        return $this->apiResponse($result['data'])
            ->withMeta($result['meta']);
    }

    #[Endpoint('/users/{id}')]
    public function show(Request $request, string $id): Response
    {
        $userId = (int) $id;
        try {
            $user = User::findById($userId);
            return $this->apiResponse($user);
        } catch (UserNotFoundException $e) {
            return $this->apiError('User not found', 404);
        }
    }

    #[Endpoint('/users/export')]
    public function export(Request $request): Response
    {
        $data = [];
        return $this->csvResponse($data, 'users_export.csv');
    }
}
