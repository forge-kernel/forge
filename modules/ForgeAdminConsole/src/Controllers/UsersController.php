<?php
declare(strict_types=1);

namespace App\Modules\ForgeAdminConsole\Controllers;

use App\Modules\ForgeAdminConsole\Services\AdminUserService;
use App\Modules\ForgeAuth\Contracts\UserContextInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
#[Middleware('auth')]
#[Layout("ForgeComponents:wrappers/admin-default")]
final class UsersController
{
    use ControllerHelper;

    public function __construct(
        private readonly AdminUserService $userService,
        private readonly UserContextInterface $userContext,
    ) {
    }

    #[Route("/admin/users")]
    public function listUsers(): Response
    {
        $tableData = $this->userService->getUsersTableData();

        return $this->view(view: "pages/admin/users/list", data: [
            'columns' => $tableData['columns'],
            'rows' => $tableData['rows'],
            'currentUser' => $this->userContext->current(),
        ]);
    }

    #[Route("/admin/users/{id}")]
    public function viewUser(int $id): Response
    {
        $user = $this->userService->getUserDetails($id);

        if (!$user) {
            Flash::set('error', 'User not found.');
            return Redirect::to('/admin/users');
        }

        return $this->view(view: "pages/admin/users/user-detail", data: [
            'user' => $user,
            'currentUser' => $this->userContext->current(),
        ]);
    }
}
