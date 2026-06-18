<?php
declare(strict_types=1);

namespace App\Modules\ForgeAdminConsole\Controllers;

use App\Modules\ForgeAuth\Contracts\UserContextInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Attributes\Middleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Routing\Route;
use App\Modules\ForgeRouter\Traits\ControllerHelper;
use Forge\Traits\SecurityHelper;

#[Service]
#[Middleware('web')]
#[Middleware('auth')]
#[Layout("ForgeComponents:wrappers/admin-default")]
final class AccountController
{
    use ControllerHelper;
    use SecurityHelper;

    public function __construct(
        private readonly UserContextInterface $userContext,
    ) {
    }

    #[Route("/admin/account")]
    public function editAccount(): Response
    {
        $user = $this->userContext->current();
        return $this->view(view: "pages/admin/account", data: [
            'currentUser' => $user,
        ]);
    }

    #[Route("/admin/account", "POST")]
    public function saveAccount(Request $request): Response
    {
        try {
            $this->sanitize($request->postData);
            Flash::set('success', 'Account settings updated successfully.');
        } catch (\Throwable $e) {
            Flash::set('error', 'Failed to update account: ' . $e->getMessage());
        }

        return Redirect::to('/admin/account');
    }
}
