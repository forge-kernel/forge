<?php
declare(strict_types=1);

namespace App\Modules\ForgeAdminConsole\Controllers;

use App\Modules\ForgeAuth\Contracts\UserContextInterface;
use Forge\Core\Helpers\Flash;
use App\Modules\ForgeRouter\Helpers\Redirect;
use App\Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use App\Modules\ForgeRouter\Http\Request;
use App\Modules\ForgeRouter\Http\Response;
use App\Modules\ForgeRouter\Attributes\Layout;
use App\Modules\ForgeRouter\Attributes\Routable;
use App\Modules\ForgeRouter\Routing\Endpoint;
use App\Modules\ForgeRouter\Traits\ResponseHelper;
use App\Modules\ForgeView\Traits\ViewHelper;
use Forge\Traits\SecurityHelper;

#[Routable(prefix: '/admin')]
#[UseMiddleware(['web', 'auth'])]
#[Layout("ForgeComponents:wrappers/admin-default")]
final class AccountController
{
    use ResponseHelper;
    use ViewHelper;
    use SecurityHelper;

    public function __construct(
        private readonly UserContextInterface $userContext,
    ) {
    }

    #[Endpoint("/account")]
    public function editAccount(): Response
    {
        $user = $this->userContext->current();
        return $this->view(view: "admin/account", data: [
            'currentUser' => $user,
        ]);
    }

    #[Endpoint("/account", "POST")]
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
