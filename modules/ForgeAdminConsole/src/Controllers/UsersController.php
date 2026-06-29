<?php
declare(strict_types=1);

namespace Modules\ForgeAdminConsole\Controllers;

use Modules\ForgeAdminConsole\Services\AdminUserService;
use Modules\ForgeAuth\Contracts\UserContextInterface;
use Forge\Core\Helpers\Flash;
use Modules\ForgeRouter\Helpers\Redirect;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;

#[Routable]
#[UseMiddleware(['web', 'auth'])]
#[Layout("ForgeComponents:wrappers/admin-default")]
final class UsersController
{
    use ResponseHelper;
    use ViewHelper;

    public function __construct(
        private readonly AdminUserService $userService,
        private readonly UserContextInterface $userContext,
    ) {
    }

    #[Endpoint("/admin/users")]
    public function listUsers(): Response
    {
        $tableData = $this->userService->getUsersTableData();

        return $this->view(view: "admin/users/list", data: [
            'columns' => $tableData['columns'],
            'rows' => $tableData['rows'],
            'currentUser' => $this->userContext->current(),
        ]);
    }

    #[Endpoint("/admin/users/{id}")]
    public function viewUser(int $id): Response
    {
        $user = $this->userService->getUserDetails($id);

        if (!$user) {
            Flash::set('error', 'User not found.');
            return Redirect::to('/admin/users');
        }

        return $this->view(view: "admin/users/user-detail", data: [
            'user' => $user,
            'currentUser' => $this->userContext->current(),
        ]);
    }
}
