<?php

declare(strict_types=1);

namespace App\Modules\AppAuth\Controllers\Api;

use App\Modules\ForgeAuth\Enums\Permission;
use App\Modules\AppAuth\Models\User;
use App\Modules\ForgeRouter\Http\Attributes\ApiRoute;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Routable;
use Forge\Exceptions\UserNotFoundException;
use App\Modules\ForgeRouter\Traits\AuthorizeRequests;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeRouter\Traits\PaginationHelper;

#[Routable]
#[UseMiddleware("api")]
final class ApiUserController
{
    use ResponseHelper;
    use AuthorizeRequests;
    use PaginationHelper;

    public function __construct()
    {
    }

    #[ApiRoute("/users", permissions: [Permission::USER_READ->value])]
    public function index(Request $request): Response
    {
        $this->authorize($request, [Permission::USER_READ->value]);

        $paginationParams = $this->getPaginationParamsForApi($request);

        $searchFields = ["email", "identifier", "status"];

        $paginator = User::paginate(
            $paginationParams["page"],
            $paginationParams["limit"],
            $paginationParams["column"],
            $paginationParams["direction"],
            $paginationParams["search"],
            [
                "searchFields" => $searchFields,
                "filters" => $paginationParams["filters"],
                "baseUrl" => $paginationParams["baseUrl"],
                "queryParams" => $paginationParams["queryParams"],
            ],
        );

        return $this->apiResponse($paginator->items())->withMeta(
            $paginator->meta(),
        );
    }

    #[ApiRoute("/users/{id}", "GET", ["api"])]
    public function show(Request $request, string $id): Response
    {
        $userId = (int) $id;
        try {
            $user = User::findById($userId);
            return $this->apiResponse($user);
        } catch (UserNotFoundException $e) {
            return $this->apiError("User not found", 404);
        }
    }

    #[ApiRoute("/users/export", "GET", ["api"])]
    public function export(Request $request): Response
    {
        $data = [];
        return $this->csvResponse($data, "users_export.csv");
    }
}
