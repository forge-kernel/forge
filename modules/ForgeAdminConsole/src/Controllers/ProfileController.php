<?php
declare(strict_types=1);

namespace Modules\ForgeAdminConsole\Controllers;

use Modules\ForgeAuth\Contracts\UserContextInterface;
use Forge\Core\Helpers\Flash;
use Modules\ForgeRouter\Helpers\Redirect;
use Modules\ForgeRouter\Http\Attributes\UseMiddleware;
use Modules\ForgeRouter\Http\Request;
use Modules\ForgeRouter\Http\Response;
use Modules\ForgeRouter\Attributes\Layout;
use Modules\ForgeRouter\Routing\Endpoint;
use Modules\ForgeRouter\Attributes\Routable;
use Modules\ForgeRouter\Traits\ResponseHelper;
use Modules\ForgeView\Traits\ViewHelper;
use Forge\Traits\SecurityHelper;

#[Routable]
#[UseMiddleware(['web', 'auth'])]
#[Layout("ForgeComponents:wrappers/admin-default")]
final class ProfileController
{
    use ResponseHelper;
    use ViewHelper;
    use SecurityHelper;

    public function __construct(
        private readonly UserContextInterface $userContext,
    ) {
    }

    #[Endpoint("/admin/profile")]
    public function editProfile(): Response
    {
        $user = $this->userContext->current();
        return $this->view(view: "admin/profile", data: [
            'currentUser' => $user,
        ]);
    }

    #[Endpoint("/admin/profile", "POST")]
    public function saveProfile(Request $request): Response
    {
        try {
            $this->sanitize($request->postData);
            Flash::set('success', 'Profile updated successfully.');
        } catch (\Throwable $e) {
            Flash::set('error', 'Failed to update profile: ' . $e->getMessage());
        }

        return Redirect::to('/admin/profile');
    }
}
